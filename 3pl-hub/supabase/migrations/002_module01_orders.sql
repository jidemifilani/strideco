-- ============================================================
-- 002: Module 01 — Order Management System
-- ============================================================

-- 3PL Clients
CREATE TABLE IF NOT EXISTS public.clients_3pl (
  id              UUID DEFAULT gen_random_uuid() PRIMARY KEY,
  company_name    TEXT NOT NULL,
  contact_name    TEXT NOT NULL,
  contact_email   TEXT NOT NULL,
  contact_phone   TEXT NOT NULL,
  address         TEXT,
  industry        TEXT,
  tax_id          TEXT,
  kyc_status      TEXT DEFAULT 'pending'
                  CHECK (kyc_status IN ('pending','in_review','approved','rejected')),
  kyc_documents   JSONB DEFAULT '[]',
  tier            TEXT DEFAULT 'standard'
                  CHECK (tier IN ('standard','silver','gold','platinum')),
  contract_start  DATE,
  contract_end    DATE,
  credit_limit    NUMERIC DEFAULT 0,
  created_by      UUID REFERENCES public.profiles(id),
  created_at      TIMESTAMPTZ DEFAULT NOW(),
  updated_at      TIMESTAMPTZ DEFAULT NOW()
);

ALTER TABLE public.clients_3pl ENABLE ROW LEVEL SECURITY;

CREATE TRIGGER clients_updated_at
  BEFORE UPDATE ON public.clients_3pl
  FOR EACH ROW EXECUTE FUNCTION public.set_updated_at();

-- Orders
CREATE TABLE IF NOT EXISTS public.orders (
  id               UUID DEFAULT gen_random_uuid() PRIMARY KEY,
  order_number     TEXT UNIQUE NOT NULL,
  client_id        UUID REFERENCES public.clients_3pl(id) ON DELETE SET NULL,
  status           TEXT DEFAULT 'pending'
                   CHECK (status IN ('pending','confirmed','dispatched','in_transit','delivered','failed','returned','cancelled')),
  total_items      INT DEFAULT 1 CHECK (total_items > 0),
  total_weight_kg  NUMERIC,
  pickup_address   TEXT NOT NULL,
  delivery_address TEXT NOT NULL,
  delivery_date    DATE,
  sla_hours        INT DEFAULT 24 CHECK (sla_hours > 0),
  priority         TEXT DEFAULT 'normal' CHECK (priority IN ('low','normal','high','urgent')),
  notes            TEXT,
  failure_reason   TEXT,
  created_by       UUID REFERENCES public.profiles(id),
  confirmed_at     TIMESTAMPTZ,
  dispatched_at    TIMESTAMPTZ,
  delivered_at     TIMESTAMPTZ,
  created_at       TIMESTAMPTZ DEFAULT NOW(),
  updated_at       TIMESTAMPTZ DEFAULT NOW()
);

ALTER TABLE public.orders ENABLE ROW LEVEL SECURITY;

CREATE TRIGGER orders_updated_at
  BEFORE UPDATE ON public.orders
  FOR EACH ROW EXECUTE FUNCTION public.set_updated_at();

CREATE INDEX idx_orders_client_id ON public.orders(client_id);
CREATE INDEX idx_orders_status    ON public.orders(status);
CREATE INDEX idx_orders_created   ON public.orders(created_at DESC);

-- Order Items
CREATE TABLE IF NOT EXISTS public.order_items (
  id           UUID DEFAULT gen_random_uuid() PRIMARY KEY,
  order_id     UUID NOT NULL REFERENCES public.orders(id) ON DELETE CASCADE,
  description  TEXT NOT NULL,
  quantity     INT DEFAULT 1 CHECK (quantity > 0),
  weight_kg    NUMERIC,
  barcode      TEXT,
  sku          TEXT,
  created_at   TIMESTAMPTZ DEFAULT NOW()
);

ALTER TABLE public.order_items ENABLE ROW LEVEL SECURITY;
CREATE INDEX idx_order_items_order ON public.order_items(order_id);

-- Order Status History (audit log)
CREATE TABLE IF NOT EXISTS public.order_status_history (
  id          UUID DEFAULT gen_random_uuid() PRIMARY KEY,
  order_id    UUID NOT NULL REFERENCES public.orders(id) ON DELETE CASCADE,
  old_status  TEXT,
  new_status  TEXT NOT NULL,
  changed_by  UUID REFERENCES public.profiles(id),
  notes       TEXT,
  created_at  TIMESTAMPTZ DEFAULT NOW()
);

ALTER TABLE public.order_status_history ENABLE ROW LEVEL SECURITY;
CREATE INDEX idx_osh_order ON public.order_status_history(order_id);

-- RLS Policies
CREATE POLICY "Internal staff can view all orders"
  ON public.orders FOR SELECT
  USING (
    EXISTS (
      SELECT 1 FROM public.profiles
      WHERE id = auth.uid() AND role IN ('super_admin','admin','dispatcher','driver')
    )
  );

CREATE POLICY "Clients can view their own orders"
  ON public.orders FOR SELECT
  USING (
    EXISTS (
      SELECT 1 FROM public.profiles
      WHERE id = auth.uid() AND client_id = orders.client_id
    )
  );

CREATE POLICY "Dispatchers and above can insert orders"
  ON public.orders FOR INSERT
  WITH CHECK (
    EXISTS (
      SELECT 1 FROM public.profiles
      WHERE id = auth.uid() AND role IN ('super_admin','admin','dispatcher')
    )
  );

CREATE POLICY "Dispatchers and above can update orders"
  ON public.orders FOR UPDATE
  USING (
    EXISTS (
      SELECT 1 FROM public.profiles
      WHERE id = auth.uid() AND role IN ('super_admin','admin','dispatcher')
    )
  );
