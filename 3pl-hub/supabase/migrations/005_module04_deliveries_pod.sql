-- ============================================================
-- 005: Module 04 — Delivery Execution & POD
-- ============================================================

CREATE TABLE IF NOT EXISTS public.deliveries (
  id               UUID DEFAULT gen_random_uuid() PRIMARY KEY,
  order_id         UUID NOT NULL REFERENCES public.orders(id) ON DELETE CASCADE,
  route_id         UUID REFERENCES public.routes(id) ON DELETE SET NULL,
  route_stop_id    UUID REFERENCES public.route_stops(id) ON DELETE SET NULL,
  driver_id        UUID NOT NULL REFERENCES public.drivers(id),
  status           TEXT DEFAULT 'pending'
                   CHECK (status IN ('pending','in_transit','delivered','failed','rescheduled','returned')),
  attempt_count    INT DEFAULT 0,
  attempted_at     TIMESTAMPTZ,
  delivered_at     TIMESTAMPTZ,
  failure_reason   TEXT,
  reschedule_date  DATE,
  return_reason    TEXT,
  created_at       TIMESTAMPTZ DEFAULT NOW(),
  updated_at       TIMESTAMPTZ DEFAULT NOW()
);

ALTER TABLE public.deliveries ENABLE ROW LEVEL SECURITY;

CREATE TRIGGER deliveries_updated_at
  BEFORE UPDATE ON public.deliveries
  FOR EACH ROW EXECUTE FUNCTION public.set_updated_at();

CREATE INDEX idx_del_order   ON public.deliveries(order_id);
CREATE INDEX idx_del_driver  ON public.deliveries(driver_id);
CREATE INDEX idx_del_status  ON public.deliveries(status);

-- Electronic Proof of Delivery
CREATE TABLE IF NOT EXISTS public.pod_records (
  id               UUID DEFAULT gen_random_uuid() PRIMARY KEY,
  delivery_id      UUID NOT NULL REFERENCES public.deliveries(id) ON DELETE CASCADE,
  recipient_name   TEXT NOT NULL,
  recipient_id_type TEXT,
  recipient_id_num  TEXT,
  signature_url    TEXT,          -- Supabase Storage URL
  photo_url        TEXT,          -- Timestamped delivery photo
  photo_url_2      TEXT,          -- Optional second photo
  gps_lat          NUMERIC(10,7),
  gps_lng          NUMERIC(10,7),
  gps_accuracy_m   NUMERIC,
  barcode_scanned  TEXT,          -- Shipment barcode / QR
  device_id        TEXT,          -- Driver's mobile device ID
  app_version      TEXT,
  notes            TEXT,
  offline_synced   BOOLEAN DEFAULT FALSE,
  created_at       TIMESTAMPTZ DEFAULT NOW()
);

ALTER TABLE public.pod_records ENABLE ROW LEVEL SECURITY;
CREATE INDEX idx_pod_delivery ON public.pod_records(delivery_id);

-- Failed Delivery Records
CREATE TABLE IF NOT EXISTS public.failed_deliveries (
  id                UUID DEFAULT gen_random_uuid() PRIMARY KEY,
  delivery_id       UUID NOT NULL REFERENCES public.deliveries(id),
  attempt_number    INT NOT NULL,
  reason_code       TEXT NOT NULL CHECK (reason_code IN (
                    'no_one_home','wrong_address','refused','damaged','access_denied','other'
                  )),
  notes             TEXT,
  photo_url         TEXT,
  gps_lat           NUMERIC(10,7),
  gps_lng           NUMERIC(10,7),
  rescheduled_for   DATE,
  resolution        TEXT CHECK (resolution IN ('rescheduled','returned_to_sender','disposed','pending')),
  created_by        UUID REFERENCES public.profiles(id),
  created_at        TIMESTAMPTZ DEFAULT NOW()
);

ALTER TABLE public.failed_deliveries ENABLE ROW LEVEL SECURITY;
CREATE INDEX idx_failed_delivery ON public.failed_deliveries(delivery_id);

-- RLS
CREATE POLICY "Staff can view deliveries"
  ON public.deliveries FOR SELECT
  USING (
    EXISTS (SELECT 1 FROM public.profiles WHERE id = auth.uid() AND role IN ('super_admin','admin','dispatcher'))
    OR
    EXISTS (
      SELECT 1 FROM public.drivers d WHERE d.id = deliveries.driver_id AND d.profile_id = auth.uid()
    )
  );

CREATE POLICY "Drivers can update their deliveries"
  ON public.deliveries FOR UPDATE
  USING (
    EXISTS (
      SELECT 1 FROM public.drivers d WHERE d.id = deliveries.driver_id AND d.profile_id = auth.uid()
    )
  );

CREATE POLICY "Drivers can insert POD"
  ON public.pod_records FOR INSERT
  WITH CHECK (
    EXISTS (
      SELECT 1 FROM public.deliveries del
      JOIN public.drivers d ON d.id = del.driver_id
      WHERE del.id = pod_records.delivery_id AND d.profile_id = auth.uid()
    )
  );
