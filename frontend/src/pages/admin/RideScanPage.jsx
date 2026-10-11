import { useEffect, useState } from 'react';
import ScanStation from '../../components/ScanStation';
import Alert, { ErrorAlert } from '../../components/Alert';
import { getAttractions } from '../../api/attractionApi';
import { recordRideScan } from '../../api/eventApi';
import { tierNameFromCode } from '../../utils/format';

// Ride scan (design/Screens.md section 12, FR-A-07). Staff at one ride pick it once:
// the choice is remembered in this browser (localStorage).

const LAST_RIDE_KEY = 'wl-last-ride-id';

function readLastRideId() {
  try {
    return localStorage.getItem(LAST_RIDE_KEY) || '';
  } catch {
    return '';
  }
}

export default function RideScanPage() {
  const [rides, setRides] = useState([]);
  const [ridesError, setRidesError] = useState(null);
  const [selectedRideId, setSelectedRideId] = useState(readLastRideId());

  function loadRides() {
    setRidesError(null);
    getAttractions()
      .then(setRides)
      .catch(setRidesError);
  }

  useEffect(loadRides, []);

  const selectedRide = rides.find((ride) => String(ride.id) === selectedRideId);

  function handleRideChange(rideId) {
    setSelectedRideId(rideId);
    try {
      localStorage.setItem(LAST_RIDE_KEY, rideId);
    } catch {
      // Private mode can block storage. Staff just choose the ride again next time.
    }
  }

  async function recordRide(reference) {
    const scan = await recordRideScan(reference, Number(selectedRideId));

    return {
      title: 'Enjoy the ride!',
      detail: `${scan.attraction_name} · ${tierNameFromCode(scan.tier_code)} ticket`,
      warning: scan.warning,
    };
  }

  return (
    <div className="wl-stack">
      <h1>Ride scan{selectedRide ? ` — ${selectedRide.name}` : ''}</h1>

      {ridesError && <ErrorAlert error={ridesError} onRetry={loadRides} title="We can't load the rides" />}
      <div className="wl-field" style={{ maxWidth: 'var(--form-max)' }}>
        <label className="wl-label" htmlFor="ride">Ride</label>
        <select id="ride" className="wl-select wl-input--lg" value={selectedRide ? selectedRideId : ''} onChange={(event) => handleRideChange(event.target.value)}>
          <option value="">Choose a ride</option>
          {rides.map((ride) => <option key={ride.id} value={ride.id}>{ride.name}</option>)}
        </select>
      </div>

      {/* If attraction-service was down the scan is still saved; the ride name comes later (graceful degradation). */}
      <ScanStation
        recordScan={recordRide}
        submitLabel="Record ride"
        isEnabled={Boolean(selectedRide)}
        disabledHint="Choose a ride to start scanning."
        extraResult={(result) => result.warning && <Alert variant="info" title="Ride saved">The ride name will appear later.</Alert>}
      />
    </div>
  );
}
