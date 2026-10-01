-- ============================================================
-- 004: Module 03 — Route Optimisation Engine
-- ============================================================

CREATE TABLE IF NOT EXISTS public.routes (
  id                      UUID DEFAULT gen_random_uuid() PRIMARY KEY,
  route_name              TEXT NOT NULL,
  vehicle_id              UUID NOT NULL REFERENCES public.vehicles(id),
  driver_id               UUID NOT NULL REFERENCES public.drivers(id),
  dispatch_date           DATE NOT NULL DEFAULT CURRENT_DATE,
  status                  TEXT DEFAULT 'planned'
                          CHECK (status IN ('planned','dispatched','in_progress','completed','cancelled')),
  total_stops             INT DEFAULT 0,
  estimated_distance_km   NUMERIC,
  estimated_duration_mins INT,
  actual_distance_km      NUMERIC,
  actual_duration_mins    INT,
  cost_estimate           NUMERIC,
  actual_cost             NUMERIC,
  optimization_score      NUMERIC,   -- 0-100, AI routing quality score
  notes                   TEXT,
  created_by              UUID REFERENCES public.profiles(id),
  dispatched_at           TIMESTAMPTZ,
  completed_at            TIMESTAMPTZ,
  created_at              TIMESTAMPTZ DEFAULT NOW(),
  updated_at              TIMESTAMPTZ DEFAULT NOW()
);

ALTER TABLE public.routes ENABLE ROW LEVEL SECURITY;

CREATE TRIGGER routes_updated_at
  BEFORE UPDATE ON public.routes
  FOR EACH ROW EXECUTE FUNCTION public.set_updated_at();

CREATE INDEX idx_routes_dispatch_date ON public.routes(dispatch_date DESC);
CREATE INDEX idx_routes_status        ON public.routes(status);
CREATE INDEX idx_routes_driver        ON public.routes(driver_id);
CREATE INDEX idx_routes_vehicle       ON public.routes(vehicle_id);

-- Route Stops (sequenced delivery points per route)
CREATE TABLE IF NOT EXISTS public.route_stops (
  id                  UUID DEFAULT gen_random_uuid() PRIMARY KEY,
  route_id            UUID NOT NULL REFERENCES public.routes(id) ON DELETE CASCADE,
  order_id            UUID REFERENCES public.orders(id),
  sequence_number     INT NOT NULL,
  address             TEXT NOT NULL,
  contact_name        TEXT,
  contact_phone       TEXT,
  time_window_start   TIME,
  time_window_end     TIME,
  estimated_arrival   TIMESTAMPTZ,
  actual_arrival      TIMESTAMPTZ,
  status              TEXT DEFAULT 'pending'
                      CHECK (status IN ('pending','en_route','arrived','completed','failed','skipped')),
  failure_reason      TEXT,
  notes               TEXT,
  arrived_at          TIMESTAMPTZ,
  completed_at        TIMESTAMPTZ,
  created_at          TIMESTAMPTZ DEFAULT NOW(),
  UNIQUE (route_id, sequence_number)
);

ALTER TABLE public.route_stops ENABLE ROW LEVEL SECURITY;
CREATE INDEX idx_stops_route    ON public.route_stops(route_id);
CREATE INDEX idx_stops_order    ON public.route_stops(order_id);

-- RLS
CREATE POLICY "Staff can view routes"
  ON public.routes FOR SELECT
  USING (
    EXISTS (SELECT 1 FROM public.profiles WHERE id = auth.uid() AND role IN ('super_admin','admin','dispatcher','driver'))
  );

CREATE POLICY "Dispatchers can manage routes"
  ON public.routes FOR ALL
  USING (
    EXISTS (SELECT 1 FROM public.profiles WHERE id = auth.uid() AND role IN ('super_admin','admin','dispatcher'))
  );

CREATE POLICY "Drivers can view their route stops"
  ON public.route_stops FOR SELECT
  USING (
    EXISTS (
      SELECT 1 FROM public.routes r
      JOIN public.drivers d ON d.id = r.driver_id
      WHERE r.id = route_stops.route_id AND d.profile_id = auth.uid()
    )
    OR
    EXISTS (SELECT 1 FROM public.profiles WHERE id = auth.uid() AND role IN ('super_admin','admin','dispatcher'))
  );
