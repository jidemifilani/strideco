# Supabase Migrations

Run these in order against your Supabase project via the SQL Editor or `supabase db push`.

| File | Module | Description |
|------|--------|-------------|
| `001_profiles_and_roles.sql` | Core | User profiles, role enum, auto-create trigger, RLS bootstrap |
| `002_module01_orders.sql` | M01 | clients_3pl, orders, order_items, order_status_history |
| `003_module02_fleet.sql` | M02 | vehicles, drivers, maintenance_records, fuel_logs, vehicle_telemetry |
| `004_module03_routes.sql` | M03 | routes, route_stops |
| `005_module04_deliveries_pod.sql` | M04 | deliveries, pod_records, failed_deliveries |
| `006_module06_billing.sql` | M06 | rate_cards, invoices, invoice_line_items, revenue_summary view |

## Roles

| Role | Access |
|------|--------|
| `super_admin` | Full access to all data |
| `admin` | Full operational access |
| `dispatcher` | Orders, routes, fleet read |
| `driver` | Own deliveries, POD submission |
| `client_admin` | Own client data, invoices |
| `client_staff` | Own client orders read-only |

## Storage Buckets (create manually in Supabase dashboard)

- `pod-photos` — Delivery proof photos (private)
- `pod-signatures` — Digital signatures (private)
- `kyc-documents` — Client KYC uploads (private)
