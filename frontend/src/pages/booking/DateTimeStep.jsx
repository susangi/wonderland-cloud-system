import { useEffect, useState } from 'react';
import DatePicker from '../../components/DatePicker';
import TimeSlotList from '../../components/TimeSlotList';
import Alert, { ErrorAlert } from '../../components/Alert';
import StepFooter from './StepFooter';
import { getTimeSlots } from '../../api/bookingApi';

// Step 2: visit date (today to 90 days ahead) and a time slot with enough places (FR-V-09).
export default function DateTimeStep({ visitDate, onDateChange, guests, selectedSlot, onSelectSlot, notice, onBack, onContinue, shortSummary }) {
  const [slots, setSlots] = useState([]);
  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState(null);

  function loadSlots() {
    if (!visitDate) return;

    setIsLoading(true);
    setError(null);
    getTimeSlots(visitDate)
      .then(setSlots)
      .catch(setError)
      .finally(() => setIsLoading(false));
  }

  useEffect(loadSlots, [visitDate]);

  return (
    <>
      <h2>Pick a date and time</h2>
      {notice && <Alert variant="warning" title={notice} />}

      <div className="wl-date-time">
        <DatePicker selectedDate={visitDate} onSelect={onDateChange} />

        <div className="wl-stack" aria-busy={isLoading}>
          {!visitDate && <p className="wl-muted">Pick a date to see times.</p>}
          {isLoading && [1, 2, 3, 4].map((key) => <div key={key} className="wl-skeleton" style={{ height: 96 }} aria-hidden="true" />)}
          {error && <ErrorAlert error={error} onRetry={loadSlots} />}
          {visitDate && !isLoading && !error && (
            <TimeSlotList slots={slots} guests={guests} selectedSlotId={selectedSlot?.id} onSelect={onSelectSlot} />
          )}
        </div>
      </div>

      <StepFooter onBack={onBack} onContinue={onContinue} canContinue={visitDate !== null && selectedSlot !== null} shortSummary={shortSummary} />
    </>
  );
}
