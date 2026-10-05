-- ============================================================
-- 006: Module 06 — Billing & Revenue Management
-- ============================================================

-- Rate Cards (per-client pricing rules)
CREATE TABLE IF NOT EXISTS public.rate_cards (
  id              UUID DEFAULT gen_random_uuid() PRIMARY KEY,
  client_id       UUID REFERENCES public.clients_3pl(id) ON DELETE CASCADE,
  rate_type       TEXT NOT NULL
                  CHECK (rate_type IN ('per_km','per_delivery','per_pallet','per_zone','flat_fee')),
  rate_value      NUMERIC NOT NULL CHECK (rate_value >= 0),
  zone            TEXT,           -- For per_zone pricing
  min_charge      NUMERIC,
  max_charge      NUMERIC,
  currency        TEXT DEFAULT 'NGN',
  effective_from  DATE NOT NULL DEFAULT CURRENT_DATE,
  effective_to    DATE,
  is_default      BOOLEAN DEFAULT FALSE,
  created_by      UUID REFERENCES public.profiles(id),
  created_at      TIMESTAMPTZ DEFAULT NOW(),
  updated_at      TIMESTAMPTZ DEFAULT NOW()
);

ALTER TABLE public.rate_cards ENABLE ROW LEVEL SECURITY;

CREATE TRIGGER rate_cards_updated_at
  BEFORE UPDATE ON public.rate_cards
  FOR EACH ROW EXECUTE FUNCTION public.set_updated_at();

-- Invoices
CREATE TABLE IF NOT EXISTS public.invoices (
  id              UUID DEFAULT gen_random_uuid() PRIMARY KEY,
  invoice_number  TEXT UNIQUE NOT NULL,
  client_id       UUID NOT NULL REFERENCES public.clients_3pl(id),
  status          TEXT DEFAULT 'draft'
                  CHECK (status IN ('draft','issued','paid','overdue','disputed','cancelled','credit_note')),
  total_amount    NUMERIC DEFAULT 0 CHECK (total_amount >= 0),
  tax_amount      NUMERIC DEFAULT 0,
  currency        TEXT DEFAULT 'NGN',
  issue_date      DATE DEFAULT CURRENT_DATE,
  due_date        DATE,
  paid_at         TIMESTAMPTZ,
  payment_method  TEXT,
  payment_ref     TEXT,
  dispute_reason  TEXT,
  credit_note_ref TEXT,   -- References original invoice for credit notes
  notes           TEXT,
  created_by      UUID REFERENCES public.profiles(id),
  created_at      TIMESTAMPTZ DEFAULT NOW(),
  updated_at      TIMESTAMPTZ DEFAULT NOW()
);

ALTER TABLE public.invoices ENABLE ROW LEVEL SECURITY;

CREATE TRIGGER invoices_updated_at
  BEFORE UPDATE ON public.invoices
  FOR EACH ROW EXECUTE FUNCTION public.set_updated_at();

CREATE INDEX idx_inv_client ON public.invoices(client_id);
CREATE INDEX idx_inv_status ON public.invoices(status);

-- Invoice Line Items
CREATE TABLE IF NOT EXISTS public.invoice_line_items (
  id           UUID DEFAULT gen_random_uuid() PRIMARY KEY,
  invoice_id   UUID NOT NULL REFERENCES public.invoices(id) ON DELETE CASCADE,
  delivery_id  UUID REFERENCES public.deliveries(id),
  description  TEXT NOT NULL,
  quantity     NUMERIC DEFAULT 1 CHECK (quantity > 0),
  unit_rate    NUMERIC NOT NULL,
  total        NUMERIC GENERATED ALWAYS AS (quantity * unit_rate) STORED,
  tax_rate     NUMERIC DEFAULT 0,
  created_at   TIMESTAMPTZ DEFAULT NOW()
);

ALTER TABLE public.invoice_line_items ENABLE ROW LEVEL SECURITY;
CREATE INDEX idx_line_items_invoice ON public.invoice_line_items(invoice_id);

-- Revenue Summary View
CREATE OR REPLACE VIEW public.revenue_summary AS
SELECT
  c.id AS client_id,
  c.company_name,
  COUNT(DISTINCT i.id)                              AS total_invoices,
  COALESCE(SUM(i.total_amount), 0)                  AS total_billed,
  COALESCE(SUM(CASE WHEN i.status = 'paid' THEN i.total_amount ELSE 0 END), 0) AS total_collected,
  COALESCE(SUM(CASE WHEN i.status IN ('issued','overdue') THEN i.total_amount ELSE 0 END), 0) AS outstanding,
  MAX(i.issue_date)                                 AS last_invoice_date
FROM public.clients_3pl c
LEFT JOIN public.invoices i ON i.client_id = c.id
GROUP BY c.id, c.company_name;

-- RLS
CREATE POLICY "Finance staff can view invoices"
  ON public.invoices FOR SELECT
  USING (
    EXISTS (SELECT 1 FROM public.profiles WHERE id = auth.uid() AND role IN ('super_admin','admin'))
    OR
    EXISTS (
      SELECT 1 FROM public.profiles p
      WHERE p.id = auth.uid() AND p.client_id = invoices.client_id
    )
  );

CREATE POLICY "Admin can manage invoices"
  ON public.invoices FOR ALL
  USING (
    EXISTS (SELECT 1 FROM public.profiles WHERE id = auth.uid() AND role IN ('super_admin','admin'))
  );

-- Auto invoice number generator
CREATE OR REPLACE FUNCTION public.generate_invoice_number()
RETURNS TEXT LANGUAGE plpgsql AS $$
DECLARE
  seq INT;
BEGIN
  SELECT COALESCE(MAX(CAST(SUBSTRING(invoice_number FROM 8) AS INT)), 0) + 1
  INTO seq
  FROM public.invoices
  WHERE invoice_number LIKE 'SBL-INV-%';

  RETURN 'SBL-INV-' || LPAD(seq::TEXT, 5, '0');
END;
$$;
