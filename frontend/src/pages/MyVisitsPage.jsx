import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { DoorOpen } from 'lucide-react';
import Timeline from '../components/Timeline';
import KpiCard from '../components/KpiCard';
import EmptyState from '../components/EmptyState';
import { ErrorAlert } from '../components/Alert';
import { SkeletonRows } from '../components/LoadingState';
import { getMyVisits } from '../api/eventApi';
import { getAttractions } from '../api/attractionApi';
import { formatCount } from '../utils/format';

// Visit history: gate check-ins and rides used (design/Screens.md section 7, FR-V-14).
export default function MyVisitsPage() {
  const [visits, setVisits] = useState([]);
  const [categoryById, setCategoryById] = useState({});
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState(null);

  function loadVisits() {
    setIsLoading(true);
    setError(null);
    getMyVisits()
      .then(setVisits)
      .catch(setError)
      .finally(() => setIsLoading(false));

    // Only for the small category line under each ride. Not important if it fails.
    getAttractions()
      .then((attractions) => setCategoryById(Object.fromEntries(attractions.map((item) => [item.id, item.category]))))
      .catch(() => setCategoryById({}));
  }

  useEffect(loadVisits, []);

  const checkIns = visits.filter((visit) => visit.event_type === 'GATE_CHECKIN');
  const rides = visits.filter((visit) => visit.event_type === 'RIDE_USAGE');

  return (
    <div className="wl-container wl-stack">
      <h1>My visits</h1>

      {isLoading && <SkeletonRows count={6} label="Loading your visits…" />}
      {error && <ErrorAlert error={error} onRetry={loadVisits} />}
      {!isLoading && !error && visits.length === 0 && (
        <EmptyState
          icon={DoorOpen}
          title="No visits yet"
          text="Your gate check-ins and rides will show up after your visit."
          action={<Link className="wl-btn wl-btn--primary" to="/my/bookings">View my tickets</Link>}
        />
      )}
      {!isLoading && !error && visits.length > 0 && (
        <div className="wl-visits-layout">
          <Timeline visits={visits} categoryById={categoryById} />
          <div className="wl-stats">
            <KpiCard label="Visits" value={formatCount(checkIns.length)} />
            <KpiCard label="Rides used" value={formatCount(rides.length)} />
            <KpiCard label="Favourite ride" value={favouriteRide(rides) || '–'} />
          </div>
        </div>
      )}
    </div>
  );
}

// The ride used most often.
function favouriteRide(rides) {
  const uses = {};
  rides.forEach((ride) => { uses[ride.attraction_name] = (uses[ride.attraction_name] || 0) + 1; });

  return Object.keys(uses).sort((first, second) => uses[second] - uses[first])[0];
}
