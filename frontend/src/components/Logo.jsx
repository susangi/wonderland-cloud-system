import { Link } from 'react-router-dom';

// Type only wordmark: coral circle with a small amber circle + "Wonderland" (design README "Logo").
export default function Logo({ to = '/' }) {
  return (
    <Link className="wl-logo" to={to}>
      <span className="wl-logo__mark" aria-hidden="true" />
      Wonderland
    </Link>
  );
}
