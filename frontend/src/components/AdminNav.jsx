import { NavLink, Link } from 'react-router-dom';
import { BarChart3, DoorOpen, FerrisWheel, ScanLine, Tag } from 'lucide-react';
import Logo from './Logo';
import { useAuth } from '../auth/AuthContext';

// Admin navigation (design/components/AdminNav): navy sidebar on desktop,
// 5 item bottom bar on phones so gate staff can switch screens with a thumb.
const ITEMS = [
  { to: '/admin', label: 'Monitoring', shortLabel: 'Stats', icon: BarChart3, end: true },
  { to: '/admin/attractions', label: 'Attractions', shortLabel: 'Attractions', icon: FerrisWheel },
  { to: '/admin/pricing', label: 'Pricing', shortLabel: 'Pricing', icon: Tag },
  { to: '/admin/checkin', label: 'Gate check-in', shortLabel: 'Gate', icon: DoorOpen },
  { to: '/admin/ride-scan', label: 'Ride scan', shortLabel: 'Rides', icon: ScanLine },
];

export function AdminSidebar() {
  const { user, logout } = useAuth();

  return (
    <aside className="wl-sidebar wl-on-dark">
      <Logo to="/admin" />
      <span className="wl-sidebar__group">Park staff</span>
      <nav className="wl-sidebar__nav" aria-label="Admin">
        {ITEMS.map(({ to, label, icon: Icon, end }) => (
          <NavLink key={to} to={to} end={end}>
            <Icon aria-hidden="true" />
            {label}
          </NavLink>
        ))}
      </nav>
      <div className="wl-sidebar__foot">
        <span>{user?.email}</span>
        <Link to="/">Back to site</Link>
        <button type="button" onClick={logout}>Log out</button>
      </div>
    </aside>
  );
}

export function AdminBottomNav() {
  return (
    <nav className="wl-bottomnav wl-on-dark" aria-label="Admin">
      {ITEMS.map(({ to, shortLabel, icon: Icon, end }) => (
        <NavLink key={to} to={to} end={end}>
          <Icon aria-hidden="true" />
          {shortLabel}
        </NavLink>
      ))}
    </nav>
  );
}
