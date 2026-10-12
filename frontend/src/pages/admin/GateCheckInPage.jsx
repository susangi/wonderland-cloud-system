import { useEffect, useState } from 'react';
import ScanStation from '../../components/ScanStation';
import { checkIn, getAnalyticsSummary } from '../../api/eventApi';
import { formatCount, formatShortDate, guestText, parkToday, tierNameFromCode } from '../../utils/format';

// Gate check-in (design/Screens.md section 11, FR-A-06). Works even when booking-service is down,
// because event-service checks its own copy of the tickets.
export default function GateCheckInPage() {
  const today = parkToday();
  const [guestsToday, setGuestsToday] = useState(null);

  function loadGuestsToday() {
    getAnalyticsSummary(today, today)
      .then((summary) => setGuestsToday(summary.totals.visitors))
      .catch(() => setGuestsToday(null));
  }

  useEffect(loadGuestsToday, []);

  async function recordCheckIn(reference) {
    const ticket = await checkIn(reference);
    loadGuestsToday();

    return { title: 'Welcome!', detail: `${tierNameFromCode(ticket.tier_code)} ticket – ${guestText(ticket.guests)}` };
  }

  return (
    <div className="wl-stack">
      <div>
        <h1>Gate check-in</h1>
        <p className="wl-muted">{formatShortDate(today)}</p>
      </div>
      <ScanStation recordScan={recordCheckIn} submitLabel="Check in" />
      {guestsToday !== null && <p className="wl-muted">Checked in today: {formatCount(guestsToday)} guests</p>}
    </div>
  );
}
