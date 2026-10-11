import { QRCodeSVG } from 'qrcode.react';
import StatusBadge from './StatusBadge';
import { formatDate, slotName } from '../utils/format';

// The digital ticket shown at the gate (design/components/Ticket).
// The QR code holds ONLY the booking reference, no personal data (NFR-SE-04).
export default function Ticket({ booking, footer }) {
  const isActive = booking.status === 'CONFIRMED';
  const slotTimes = (booking.time_slot_label || '').replace(slotName(booking.time_slot_label), '').trim();

  return (
    <div className={`wl-ticket${isActive ? '' : ' is-inactive'}`}>
      <div className="wl-ticket__top wl-on-dark">
        <span className="wl-logo">
          <span className="wl-logo__mark" aria-hidden="true" />
          Wonderland
        </span>
        <StatusBadge status={booking.status} />
      </div>
      <div className="wl-ticket__qr">
        <div className="wl-ticket__qr-box">
          {/* QR colours stay fixed navy on white: scanners need the highest contrast, never tinted. */}
          <QRCodeSVG value={booking.booking_reference} size={224} level="M" marginSize={0} fgColor="#0f2342" bgColor="#ffffff" title={`QR code for ${booking.booking_reference}`} />
        </div>
        <div>
          <div className="wl-overline" style={{ textAlign: 'center' }}>Booking reference</div>
          <div className="wl-ticket__ref">{booking.booking_reference}</div>
        </div>
      </div>
      <div className="wl-ticket__perf"><i /></div>
      <dl className="wl-ticket__details">
        <div><dt>Ticket</dt><dd>{booking.tier_name}</dd></div>
        <div><dt>Guests</dt><dd>{booking.guests}</dd></div>
        <div><dt>Visit date</dt><dd>{formatDate(booking.visit_date)}</dd></div>
        <div><dt>Time slot</dt><dd>{slotTimes || booking.time_slot_label}</dd></div>
      </dl>
      {footer && <div className="wl-ticket__foot wl-no-print">{footer}</div>}
    </div>
  );
}
