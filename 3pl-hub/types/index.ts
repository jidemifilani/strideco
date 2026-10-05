// ─── Auth & Profiles ─────────────────────────────────────────────────────────

export type UserRole =
  | 'super_admin'
  | 'admin'
  | 'dispatcher'
  | 'driver'
  | 'client_admin'
  | 'client_staff'

export interface Profile {
  id: string
  email: string
  full_name: string | null
  role: UserRole
  client_id: string | null
  phone: string | null
  avatar_url: string | null
  created_at: string
}

// ─── Module 01: Orders ───────────────────────────────────────────────────────

export type OrderStatus =
  | 'pending'
  | 'confirmed'
  | 'dispatched'
  | 'in_transit'
  | 'delivered'
  | 'failed'
  | 'returned'
  | 'cancelled'

export interface Order {
  id: string
  order_number: string
  client_id: string
  client?: Client3PL
  status: OrderStatus
  total_items: number
  total_weight_kg: number | null
  pickup_address: string
  delivery_address: string
  delivery_date: string | null
  sla_hours: number
  notes: string | null
  created_by: string
  created_at: string
  updated_at: string
}

export interface OrderItem {
  id: string
  order_id: string
  description: string
  quantity: number
  weight_kg: number | null
  barcode: string | null
}

// ─── Module 02: Fleet ────────────────────────────────────────────────────────

export type VehicleStatus = 'available' | 'in_use' | 'maintenance' | 'retired'
export type VehicleType = 'van' | 'truck' | 'motorcycle' | 'tricycle' | 'pickup'

export interface Vehicle {
  id: string
  registration: string
  make: string
  model: string
  year: number
  type: VehicleType
  status: VehicleStatus
  capacity_kg: number
  current_driver_id: string | null
  last_service_date: string | null
  next_service_date: string | null
  created_at: string
}

export interface Driver {
  id: string
  profile_id: string
  profile?: Profile
  license_number: string
  license_expiry: string
  status: 'available' | 'on_route' | 'off_duty' | 'suspended'
  current_vehicle_id: string | null
  vehicle?: Vehicle
  kpi_score: number
  total_deliveries: number
  created_at: string
}

export interface MaintenanceRecord {
  id: string
  vehicle_id: string
  type: 'preventive' | 'corrective' | 'inspection'
  description: string
  cost: number
  vendor: string | null
  service_date: string
  next_due_date: string | null
  created_at: string
}

export interface FuelLog {
  id: string
  vehicle_id: string
  driver_id: string
  litres: number
  cost_per_litre: number
  total_cost: number
  odometer_km: number
  logged_at: string
}

// ─── Module 03: Routes ───────────────────────────────────────────────────────

export type RouteStatus = 'planned' | 'dispatched' | 'in_progress' | 'completed' | 'cancelled'

export interface Route {
  id: string
  route_name: string
  vehicle_id: string
  vehicle?: Vehicle
  driver_id: string
  driver?: Driver
  dispatch_date: string
  status: RouteStatus
  total_stops: number
  estimated_distance_km: number | null
  estimated_duration_mins: number | null
  actual_distance_km: number | null
  cost_estimate: number | null
  created_at: string
}

export interface RouteStop {
  id: string
  route_id: string
  order_id: string
  order?: Order
  sequence_number: number
  address: string
  time_window_start: string | null
  time_window_end: string | null
  status: 'pending' | 'arrived' | 'completed' | 'failed'
  arrived_at: string | null
  completed_at: string | null
}

// ─── Module 04: Deliveries & POD ─────────────────────────────────────────────

export type DeliveryStatus =
  | 'pending'
  | 'in_transit'
  | 'delivered'
  | 'failed'
  | 'rescheduled'

export interface Delivery {
  id: string
  order_id: string
  order?: Order
  route_id: string | null
  driver_id: string
  driver?: Driver
  status: DeliveryStatus
  attempt_count: number
  attempted_at: string | null
  delivered_at: string | null
  failure_reason: string | null
  reschedule_date: string | null
  created_at: string
}

export interface PODRecord {
  id: string
  delivery_id: string
  recipient_name: string
  signature_url: string | null
  photo_url: string | null
  gps_lat: number | null
  gps_lng: number | null
  barcode_scanned: string | null
  notes: string | null
  created_at: string
}

// ─── Module 05: 3PL Clients ──────────────────────────────────────────────────

export type KYCStatus = 'pending' | 'in_review' | 'approved' | 'rejected'
export type ClientTier = 'standard' | 'silver' | 'gold' | 'platinum'

export interface Client3PL {
  id: string
  company_name: string
  contact_name: string
  contact_email: string
  contact_phone: string
  address: string | null
  industry: string | null
  kyc_status: KYCStatus
  tier: ClientTier
  contract_start: string | null
  contract_end: string | null
  created_at: string
}

// ─── Module 06: Billing ──────────────────────────────────────────────────────

export type RateType = 'per_km' | 'per_delivery' | 'per_pallet' | 'per_zone'
export type InvoiceStatus = 'draft' | 'issued' | 'paid' | 'overdue' | 'disputed' | 'cancelled'

export interface RateCard {
  id: string
  client_id: string
  rate_type: RateType
  rate_value: number
  zone: string | null
  currency: string
  effective_from: string
  effective_to: string | null
  created_at: string
}

export interface Invoice {
  id: string
  invoice_number: string
  client_id: string
  client?: Client3PL
  status: InvoiceStatus
  total_amount: number
  currency: string
  issue_date: string
  due_date: string
  paid_at: string | null
  dispute_reason: string | null
  created_at: string
}

export interface InvoiceLineItem {
  id: string
  invoice_id: string
  delivery_id: string | null
  description: string
  quantity: number
  unit_rate: number
  total: number
}

// ─── Shared ──────────────────────────────────────────────────────────────────

export interface PaginatedResponse<T> {
  data: T[]
  count: number
  page: number
  per_page: number
  total_pages: number
}

export interface DashboardStats {
  orders_today: number
  deliveries_pending: number
  fleet_active: number
  revenue_mtd: number
}
