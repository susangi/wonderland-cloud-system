import { Check, CircleAlert, ScanLine, X } from 'lucide-react';

// The big colour panel staff read at arm's length (design/components/ScanResult).
// result = null (waiting) | { kind: 'success', title, detail, reference } | { kind: 'error', error, reference }

// Turns an API error from check-in or ride scan into the words in the ScanResult guide.
export function scanErrorText(error) {
  switch (error.code) {
    case 'TICKET_NOT_FOUND':
      return { variant: 'error', title: 'Ticket not found', detail: 'Check the reference. The ticket may not be paid yet.' };
    case 'WRONG_DATE':
      return { variant: 'error', title: 'Wrong visit date', detail: error.message };
    case 'ALREADY_CHECKED_IN':
      return { variant: 'error', title: 'Already checked in', detail: error.message.replace('Already checked in ', '') };
    case 'NOT_CHECKED_IN':
      return { variant: 'error', title: 'Not checked in today', detail: 'Send the guest to the gate first.' };
    case 'ATTRACTION_NOT_AVAILABLE':
      return { variant: 'error', title: 'Ride not available', detail: 'Choose another ride.' };
    case 'VALIDATION_ERROR':
      return { variant: 'error', title: 'Check the reference', detail: 'Type the booking reference, for example WL-20261108-K7Q2ZP.' };
    default:
      // Network problems and 5xx: the scan was not recorded, so ask to scan again.
      return { variant: 'warn', title: "Couldn't record scan", detail: 'Check the connection and scan again.' };
  }
}

export default function ScanResult({ result }) {
  if (!result) {
    return (
      <div className="wl-scan wl-scan--idle" role="status" aria-live="assertive">
        <span className="wl-scan__icon" style={{ background: 'var(--surface-sunken)', color: 'var(--navy-900)' }}>
          <ScanLine aria-hidden="true" />
        </span>
        <h2 className="wl-scan__title">Ready to scan</h2>
        <p>Results appear here.</p>
      </div>
    );
  }

  const text = result.kind === 'success' ? { variant: 'success', title: result.title, detail: result.detail } : scanErrorText(result.error);
  const Icon = { success: Check, error: X, warn: CircleAlert }[text.variant];

  return (
    <div className={`wl-scan wl-scan--${text.variant}`} role="status" aria-live="assertive">
      <span className="wl-scan__icon"><Icon aria-hidden="true" /></span>
      <h2 className="wl-scan__title">{text.title}</h2>
      <p className="wl-scan__detail">{text.detail}</p>
      <p className="wl-scan__ref">{result.reference}</p>
    </div>
  );
}
