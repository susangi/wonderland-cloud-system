import { Outlet } from 'react-router-dom';
import { AdminBottomNav, AdminSidebar } from './AdminNav';

// Shell for admin pages: sidebar + main on desktop, bottom bar under 1024px.
export default function AdminLayout() {
  return (
    <div className="wl-admin">
      <a className="wl-skip-link" href="#content">Skip to content</a>
      <AdminSidebar />
      <div>
        <main id="content" className="wl-admin__main" tabIndex={-1}>
          <Outlet />
        </main>
        <AdminBottomNav />
      </div>
    </div>
  );
}
