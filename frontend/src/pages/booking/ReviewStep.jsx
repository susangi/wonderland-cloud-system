import BookingSummary from '../../components/BookingSummary';
import Alert, { ErrorAlert } from '../../components/Alert';
import StepFooter from './StepFooter';

// Step 3: check everything. "Confirm and pay" creates the booking (POST /api/bookings).
export default function ReviewStep({ summary, isSubmitting, error, onEdit, onBack, onConfirm }) {
  return (
    <>
      <h2>Check your booking</h2>

      {error?.code === 'PRICING_UNAVAILABLE' && (
        <Alert variant="warning" title="We can't load prices right now" actions={<button type="button" className="wl-btn wl-btn--sm" onClick={onConfirm}>Try again</button>}>
          Please try again shortly.
        </Alert>
      )}
      {error && error.code !== 'PRICING_UNAVAILABLE' && <ErrorAlert error={error} title="We couldn't make this booking" />}

      <BookingSummary {...summary} heading="Your booking" />
      <div className="wl-row">
        <button type="button" className="wl-btn wl-btn--ghost" onClick={() => onEdit(1)}>Edit ticket</button>
        <button type="button" className="wl-btn wl-btn--ghost" onClick={() => onEdit(2)}>Edit date</button>
      </div>

      <Alert variant="info" title="You'll have 15 minutes to pay after confirming." />

      <StepFooter onBack={onBack} onContinue={onConfirm} canContinue={!isSubmitting} isBusy={isSubmitting} continueLabel="Confirm and pay" />
    </>
  );
}
