import { useEffect, useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import { ArrowLeft, Printer, Ticket as TicketIcon } from 'lucide-react';
import Ticket from '../components/Ticket';
import Alert, { ErrorAlert } from '../components/Alert';
import EmptyState from '../components/EmptyState';
import { LoadingState } from '../components/LoadingState';
import { getBooking } from '../api/bookingApi';

// Digital ticket with QR code (design/Screens.md section 5, FR-V-11).
export default function TicketPage() {
  const { reference } = useParams();
  const [booking, setBooking] = useState(null);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState(null);

  function loadBooking() {
    setIsLoading(true);
    setError(null);
    getBooking(reference)
      .then(setBooking)
      .catch(setError)
      .finally(() => setIsLoading(false));
  }

  useEffect(loadBooking, [reference]);

  if (isLoading) {
    return <LoadingState text="Loading your ticket…" />;
  }

  // Not found or not yours: same friendly message, we do not say which.
  if (error?.status === 404 || error?.status === 403) {
    return (
      <div className="wl-container">
        <EmptyState
          icon={TicketIcon}
          title="We can't find this ticket"
          text="Check the link, or open the ticket from My bookings."
          action={<Link className="wl-btn wl-btn--primary" to="/my/bookings">Go to My bookings</Link>}
        />
      </div>
    );
  }

  if (error) {
    return <div className="wl-container"><ErrorAlert error={error} onRetry={loadBooking} /></div>;
  }

  return (
    <div className="wl-container wl-stack">
      <Link className="wl-no-print" to="/my/bookings"><ArrowLeft aria-hidden="true" style={{ display: 'inline', verticalAlign: 'middle' }} /> My bookings</Link>
      <h1 className="wl-sr-only">Ticket {booking.booking_reference}</h1>

      <div className="wl-ticket-layout">
        <div className="wl-stack">
          <StatusMessage booking={booking} />
          <Ticket booking={booking} footer={<TicketActions booking={booking} />} />
          <p className="wl-hint wl-no-print" style={{ textAlign: 'center' }}>
            Turn up your screen brightness at the gate. Tip: add this page to your home screen.
          </p>
        </div>
        <div className="wl-card wl-stack wl-no-print">
          <h2>Your visit</h2>
          <p>The gate opens at the start of your time slot.</p>
          <p>One check-in covers your whole group, so come to the gate together.</p>
          <p>Show the same QR code at each ride.</p>
        </div>
      </div>
    </div>
  );
}

// The alert above the ticket for tickets that are not active yet.
function StatusMessage({ booking }) {
  if (booking.status === 'PENDING_PAYMENT') {
    return <Alert variant="warning" title="Pay to activate this ticket">Your places are held for 15 minutes after booking.</Alert>;
  }
  if (booking.status === 'PAYMENT_FAILED') {
    return <Alert variant="danger" title="Payment failed">Try the payment again to activate this ticket.</Alert>;
  }
  return null;
}

function TicketActions({ booking }) {
  if (booking.status === 'CONFIRMED') {
    return (
      <button type="button" className="wl-btn wl-btn--block" onClick={() => window.print()}>
        <Printer aria-hidden="true" />
        Print ticket
      </button>
    );
  }
  if (booking.status === 'EXPIRED') {
    return <Link className="wl-btn wl-btn--block" to="/book">Book a new visit</Link>;
  }

  return (
    <Link className="wl-btn wl-btn--cta wl-btn--block" to={`/book?ref=${booking.booking_reference}`}>
      {booking.status === 'PAYMENT_FAILED' ? 'Try payment again' : 'Pay now'}
    </Link>
  );
}
