import { lazy, Suspense } from 'react';
import { Route, Routes } from 'react-router-dom';
import VisitorLayout from './components/VisitorLayout';
import AdminLayout from './components/AdminLayout';
import RequireAuth from './auth/RequireAuth';
import RequireAdmin from './auth/RequireAdmin';
import HomePage from './pages/HomePage';
import AttractionsPage from './pages/AttractionsPage';
import LoginPage from './pages/LoginPage';
import BookingPage from './pages/BookingPage';
import TicketPage from './pages/TicketPage';
import MyBookingsPage from './pages/MyBookingsPage';
import MyVisitsPage from './pages/MyVisitsPage';
import NotFoundPage from './pages/NotFoundPage';
import { LoadingState } from './components/LoadingState';

// Admin pages load only when staff open them, so visitors on phones do not download the charts and scanner code.
const MonitoringPage = lazy(() => import('./pages/admin/MonitoringPage'));
const ManageAttractionsPage = lazy(() => import('./pages/admin/ManageAttractionsPage'));
const ManagePricingPage = lazy(() => import('./pages/admin/ManagePricingPage'));
const GateCheckInPage = lazy(() => import('./pages/admin/GateCheckInPage'));
const RideScanPage = lazy(() => import('./pages/admin/RideScanPage'));

// All routes (design 13.3). Visitor pages share the top bar, admin pages share the sidebar.
export default function App() {
  return (
    <Suspense fallback={<LoadingState />}>
      <Routes>
        <Route element={<VisitorLayout />}>
          <Route path="/" element={<HomePage />} />
          <Route path="/attractions" element={<AttractionsPage />} />
          <Route path="/login" element={<LoginPage />} />
          <Route path="/book" element={<RequireAuth><BookingPage /></RequireAuth>} />
          <Route path="/tickets/:reference" element={<RequireAuth><TicketPage /></RequireAuth>} />
          <Route path="/my/bookings" element={<RequireAuth><MyBookingsPage /></RequireAuth>} />
          <Route path="/my/visits" element={<RequireAuth><MyVisitsPage /></RequireAuth>} />
          <Route path="*" element={<NotFoundPage />} />
        </Route>

        <Route path="/admin" element={<RequireAuth><RequireAdmin><AdminLayout /></RequireAdmin></RequireAuth>}>
          <Route index element={<MonitoringPage />} />
          <Route path="attractions" element={<ManageAttractionsPage />} />
          <Route path="pricing" element={<ManagePricingPage />} />
          <Route path="checkin" element={<GateCheckInPage />} />
          <Route path="ride-scan" element={<RideScanPage />} />
        </Route>
      </Routes>
    </Suspense>
  );
}
