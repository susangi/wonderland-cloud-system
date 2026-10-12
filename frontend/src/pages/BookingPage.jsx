import { useEffect, useState } from 'react';
import { useNavigate, useSearchParams } from 'react-router-dom';
import Stepper from '../components/Stepper';
import BookingSummary from '../components/BookingSummary';
import { ErrorAlert } from '../components/Alert';
import { LoadingState } from '../components/LoadingState';
import TicketStep from './booking/TicketStep';
import DateTimeStep from './booking/DateTimeStep';
import ReviewStep from './booking/ReviewStep';
import PaymentStep from './booking/PaymentStep';
import { getTiers } from '../api/pricingApi';
import { createBooking, getBooking } from '../api/bookingApi';
import { formatMoney } from '../utils/format';

// Booking wizard (design/Screens.md section 4): Ticket → Date & time → Review → Payment.
// ?tier=FAMILY preselects a tier (from Home). ?ref=WL-... continues paying an existing booking (from My bookings).
export default function BookingPage() {
  const [searchParams] = useSearchParams();
  const navigate = useNavigate();

  const [step, setStep] = useState(1);
  const [tiers, setTiers] = useState([]);
  const [isTiersLoading, setIsTiersLoading] = useState(true);
  const [tiersError, setTiersError] = useState(null);
  const [selectedTier, setSelectedTier] = useState(null);
  const [guests, setGuests] = useState(1);
  const [visitDate, setVisitDate] = useState(null);
  const [selectedSlot, setSelectedSlot] = useState(null);
  const [slotNotice, setSlotNotice] = useState(null);
  const [booking, setBooking] = useState(null);
  const [isCreating, setIsCreating] = useState(false);
  const [createError, setCreateError] = useState(null);
  const [resumeError, setResumeError] = useState(null);
  const resumeReference = searchParams.get('ref');

  useEffect(loadTiers, []);
  useEffect(resumeExistingBooking, [resumeReference]);

  function loadTiers() {
    setIsTiersLoading(true);
    setTiersError(null);

    getTiers()
      .then((activeTiers) => {
        setTiers(activeTiers);
        const preselected = activeTiers.find((tier) => tier.code === searchParams.get('tier'));
        if (preselected) handleSelectTier(preselected);
      })
      .catch(setTiersError)
      .finally(() => setIsTiersLoading(false));
  }

  // "Pay now" from My bookings: load the booking and go straight to payment.
  function resumeExistingBooking() {
    if (!resumeReference) return;

    getBooking(resumeReference)
      .then((existingBooking) => {
        if (existingBooking.status === 'CONFIRMED') {
          navigate(`/tickets/${existingBooking.booking_reference}`, { replace: true });
          return;
        }
        setBooking(existingBooking);
        setStep(4);
      })
      .catch(setResumeError);
  }

  // A ticket price covers the whole group, so we start with the most guests the tier allows.
  function handleSelectTier(tier) {
    setSelectedTier(tier);
    setGuests(tier.max_guests);
    setSelectedSlot(null);
  }

  function handleDateChange(date) {
    setVisitDate(date);
    setSelectedSlot(null);
  }

  // Step 3 "Confirm and pay": the booking is created now and holds the places for 15 minutes.
  async function handleConfirm() {
    setIsCreating(true);
    setCreateError(null);

    try {
      const newBooking = await createBooking({
        tier_id: selectedTier.id,
        guests,
        visit_date: visitDate,
        time_slot_id: selectedSlot.id,
      });
      setBooking(newBooking);
      setStep(4);
    } catch (error) {
      handleCreateError(error);
    } finally {
      setIsCreating(false);
    }
  }

  // Someone took the last places first: back to step 2 with fresh slots.
  function handleCreateError(error) {
    if (error.code === 'SLOT_FULL') {
      setSelectedSlot(null);
      setSlotNotice('That slot just filled up. Please choose another.');
      setStep(2);
      return;
    }
    setCreateError(error);
  }

  function handleStartAgain() {
    navigate('/book', { replace: true });
    setBooking(null);
    setSelectedSlot(null);
    setStep(1);
  }

  if (resumeError) {
    return <div className="wl-container"><ErrorAlert error={resumeError} title="We can't open this booking" /></div>;
  }

  if (resumeReference && !booking) {
    return <LoadingState text="Loading your booking…" />;
  }

  const summary = booking
    ? { tierName: booking.tier_name, guests: booking.guests, visitDate: booking.visit_date, slotLabel: booking.time_slot_label, price: booking.price_paid, currency: booking.currency }
    : { tierName: selectedTier?.name, guests: selectedTier ? guests : null, visitDate, slotLabel: selectedSlot?.label, price: selectedTier?.price, currency: selectedTier?.currency };

  const shortSummary = selectedTier ? `${formatMoney(selectedTier.price, selectedTier.currency, { decimals: 0 })} · ${selectedTier.name}, ${guests} guests` : '';
  const showSideSummary = step >= 2;

  return (
    <div className="wl-container wl-stack">
      <h1>Book tickets</h1>
      <Stepper currentStep={step} />

      <div className={`wl-booking-layout${showSideSummary ? ' has-summary' : ''}`}>
        <div className="wl-stack">
          {step === 1 && (
            <TicketStep
              tiers={tiers}
              isLoading={isTiersLoading}
              error={tiersError}
              onRetry={loadTiers}
              selectedTier={selectedTier}
              guests={guests}
              onSelectTier={handleSelectTier}
              onGuestsChange={setGuests}
              onContinue={() => setStep(2)}
              shortSummary={shortSummary}
            />
          )}
          {step === 2 && (
            <DateTimeStep
              visitDate={visitDate}
              onDateChange={handleDateChange}
              guests={guests}
              selectedSlot={selectedSlot}
              onSelectSlot={setSelectedSlot}
              notice={slotNotice}
              onBack={() => setStep(1)}
              onContinue={() => { setSlotNotice(null); setStep(3); }}
              shortSummary={shortSummary}
            />
          )}
          {step === 3 && (
            <ReviewStep
              summary={summary}
              isSubmitting={isCreating}
              error={createError}
              onEdit={setStep}
              onBack={() => setStep(2)}
              onConfirm={handleConfirm}
            />
          )}
          {step === 4 && booking && <PaymentStep booking={booking} onStartAgain={handleStartAgain} />}
        </div>

        {showSideSummary && step !== 3 && <BookingSummary {...summary} />}
      </div>
    </div>
  );
}
