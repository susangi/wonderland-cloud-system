import { Link } from 'react-router-dom';
import { DoorOpen } from 'lucide-react';
import { useAuth } from './AuthContext';
import EmptyState from '../components/EmptyState';

// Admin pages. This only hides the pages in the browser; the services return 403 for visitors anyway.
export default function RequireAdmin({ children }) {
  const { isAdmin } = useAuth();

  if (!isAdmin) {
    return <StaffOnly />;
  }

  return children;
}

export function StaffOnly() {
  return (
    <div className="wl-container wl-page">
      <EmptyState
        icon={DoorOpen}
        title="You need staff access for this page"
        text="This page is only for park staff."
        action={<Link className="wl-btn wl-btn--primary" to="/">Go home</Link>}
      />
    </div>
  );
}
