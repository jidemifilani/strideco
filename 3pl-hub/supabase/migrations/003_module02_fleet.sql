-- ============================================================
-- 003: Module 02 — Fleet Management
-- ============================================================

-- Vehicles
CREATE TABLE IF NOT EXISTS public.vehicles (
  id                  UUID DEFAULT gen_random_uuid() PRIMARY KEY,
  registration        TEXT UNIQUE NOT NULL,
  make                TEXT NOT NULL,
  model               TEXT NOT NULL,
  year                INT,
  type                TEXT DEFAULT 'van'
                      CHECK (type IN ('van','truck','motorcycle','tricycle','pickup')),
  status              TEXT DEFAULT 'available'
                      CHECK (status IN ('available','in_use','maintenance','retired')),
  capacity_kg         NUMERIC DEFAULT 0,
  capacity_pallets    INT,
  current_driver_id   UUID REFERENCES public.profiles(id),
  vin                 TEXT,
  insurance_expiry    DATE,
  road_worthiness_exp DATE,
  last_service_date   DATE,
  next_service_date   DATE,
  odometer_km         NUMERIC DEFAULT 0,
  fuel_type           TEXT DEFAULT 'petrol' CHECK (fuel_type IN ('petrol','diesel','electric','hybrid')),
  notes               TEXT,
  created_at          TIMESTAMPTZ DEFAULT NOW(),
  updated_at          TIMESTAMPTZ DEFAULT NOW()
);

ALTER TABLE public.vehicles ENABLE ROW LEVEL SECURITY;

CREATE TRIGGER vehicles_updated_at
  BEFORE UPDATE ON public.vehicles
  FOR EACH ROW EXECUTE FUNCTION public.set_updated_at();

-- Drivers
CREATE TABLE IF NOT EXISTS public.drivers (
  id                  UUID DEFAULT gen_random_uuid() PRIMARY KEY,
  profile_id          UUID UNIQUE NOT NULL REFERENCES public.profiles(id),
  license_number      TEXT UNIQUE NOT NULL,
  license_class       TEXT,
  license_expiry      DATE NOT NULL,
  status              TEXT DEFAULT 'available'
                      CHECK (status IN ('available','on_route','off_duty','suspended')),
  current_vehicle_id  UUID REFERENCES public.vehicles(id),
  kpi_score           NUMERIC DEFAULT 0 CHECK (kpi_score >= 0 AND kpi_score <= 100),
  total_deliveries    INT DEFAULT 0,
  successful_del      INT DEFAULT 0,
  hire_date           DATE,
  emergency_contact   TEXT,
  created_at          TIMESTAMPTZ DEFAULT NOW(),
  updated_at          TIMESTAMPTZ DEFAULT NOW()
);

ALTER TABLE public.drivers ENABLE ROW LEVEL SECURITY;

CREATE TRIGGER drivers_updated_at
  BEFORE UPDATE ON public.drivers
  FOR EACH ROW EXECUTE FUNCTION public.set_updated_at();

CREATE INDEX idx_drivers_status ON public.drivers(status);

-- Maintenance Records
CREATE TABLE IF NOT EXISTS public.maintenance_records (
  id            UUID DEFAULT gen_random_uuid() PRIMARY KEY,
  vehicle_id    UUID NOT NULL REFERENCES public.vehicles(id) ON DELETE CASCADE,
  type          TEXT DEFAULT 'preventive'
                CHECK (type IN ('preventive','corrective','inspection','accident_repair')),
  description   TEXT NOT NULL,
  cost          NUMERIC DEFAULT 0,
  currency      TEXT DEFAULT 'NGN',
  vendor        TEXT,
  vendor_phone  TEXT,
  service_date  DATE NOT NULL DEFAULT CURRENT_DATE,
  next_due_date DATE,
  odometer_at_service NUMERIC,
  receipt_url   TEXT,
  created_by    UUID REFERENCES public.profiles(id),
  created_at    TIMESTAMPTZ DEFAULT NOW()
);

ALTER TABLE public.maintenance_records ENABLE ROW LEVEL SECURITY;
CREATE INDEX idx_maint_vehicle ON public.maintenance_records(vehicle_id);

-- Fuel Logs
CREATE TABLE IF NOT EXISTS public.fuel_logs (
  id              UUID DEFAULT gen_random_uuid() PRIMARY KEY,
  vehicle_id      UUID NOT NULL REFERENCES public.vehicles(id) ON DELETE CASCADE,
  driver_id       UUID REFERENCES public.drivers(id),
  litres          NUMERIC NOT NULL CHECK (litres > 0),
  cost_per_litre  NUMERIC NOT NULL,
  total_cost      NUMERIC GENERATED ALWAYS AS (litres * cost_per_litre) STORED,
  odometer_km     NUMERIC,
  fuel_station    TEXT,
  logged_at       TIMESTAMPTZ DEFAULT NOW(),
  created_by      UUID REFERENCES public.profiles(id)
);

ALTER TABLE public.fuel_logs ENABLE ROW LEVEL SECURITY;
CREATE INDEX idx_fuel_vehicle ON public.fuel_logs(vehicle_id);
CREATE INDEX idx_fuel_driver  ON public.fuel_logs(driver_id);

-- GPS Telemetry (lightweight, append-only)
CREATE TABLE IF NOT EXISTS public.vehicle_telemetry (
  id           BIGSERIAL PRIMARY KEY,
  vehicle_id   UUID NOT NULL REFERENCES public.vehicles(id) ON DELETE CASCADE,
  driver_id    UUID REFERENCES public.drivers(id),
  lat          NUMERIC(10, 7) NOT NULL,
  lng          NUMERIC(10, 7) NOT NULL,
  speed_kmh    NUMERIC,
  heading_deg  NUMERIC,
  recorded_at  TIMESTAMPTZ DEFAULT NOW()
);

CREATE INDEX idx_telem_vehicle ON public.vehicle_telemetry(vehicle_id, recorded_at DESC);

-- RLS Policies
CREATE POLICY "Staff can view vehicles"
  ON public.vehicles FOR SELECT
  USING (
    EXISTS (SELECT 1 FROM public.profiles WHERE id = auth.uid() AND role IN ('super_admin','admin','dispatcher','driver'))
  );

CREATE POLICY "Admin can manage vehicles"
  ON public.vehicles FOR ALL
  USING (
    EXISTS (SELECT 1 FROM public.profiles WHERE id = auth.uid() AND role IN ('super_admin','admin'))
  );
