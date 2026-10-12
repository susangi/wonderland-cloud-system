import { Link } from 'react-router-dom';
import { CircleAlert } from 'lucide-react';
import EmptyState from '../components/EmptyState';

export default function NotFoundPage() {
  return (
    <div className="wl-container">
      <EmptyState
        icon={CircleAlert}
        title="We can't find this page"
        text="The link may be old or typed wrong."
        action={<Link className="wl-btn wl-btn--primary" to="/">Go home</Link>}
      />
    </div>
  );
}
