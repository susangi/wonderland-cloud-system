import { useEffect, useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { CreditCard } from 'lucide-react';
import Alert, { ErrorAlert } from '../../components/Alert';
import { payBooking } from '../../api/bookingApi';
import { formatMoney } from '../../utils/format';
import { PAYMENT_HOLD_MINUTES } from '../../utils/config';

// Step 4: mock card payment (FR-V-10, FR-V-12). Card details go to the API once and are not kept anywhere.

const EMPTY_CARD = { card_holder: '', card_number: '', expiry: '', cvc: '' };

// "4242424242424242" -> "4242 4242 4242 4242" while typing.
function formatCardNumber(value) {
  return value.replace(/\D/g, '').slice(0, 16).replace(/(\d{4})(?=\d)/g, '$1 ');
}

// "1228" -> "12/28" while typing.
function formatExpiry(value) {
  const digits = value.replace(/\D/g, '').slice(0, 4);
  return digits.length > 2 ? `${digits.slice(0, 2)}/${digits.slice(2)}` : digits;
}

function validateCard(card) {
  const errors = {};
  if (!card.card_holder.trim()) errors.card_holder = 'Enter the name on the card.';
  if (card.card_number.replace(/\s/g, '').length !== 16) errors.card_number = 'Enter the 16-digit card number.';
  if (!/^(0[1-9]|1[0-2])\/\d{2}$/.test(card.expiry)) errors.expiry = 'Enter the expiry as MM/YY.';
  if (!/^\d{3,4}$/.test(card.cvc)) errors.cvc = 'Enter the 3 or 4 digit CVC.';
  return errors;
}

// Seconds left of the 15 minute hold, counted from when the booking was made.
function useSecondsLeft(createdAt) {
  const deadline = new Date(createdAt).getTime() + PAYMENT_HOLD_MINUTES * 60 * 1000;
  const [secondsLeft, setSecondsLeft] = useState(Math.max(0, Math.floor((deadline - Date.now()) / 1000)));

  useEffect(() => {
    const timer = setInterval(() => setSecondsLeft(Math.max(0, Math.floor((deadline - Date.now()) / 1000))), 1000);
    return () => clearInterval(timer);
  }, [deadline]);

  return secondsLeft;
}

export default function PaymentStep({ booking, onStartAgain }) {
  const navigate = useNavigate();
  const [card, setCard] = useState(EMPTY_CARD);
  const [fieldErrors, setFieldErrors] = useState({});
  const [isPaying, setIsPaying] = useState(false);
  const [result, setResult] = useState(null);
  const secondsLeft = useSecondsLeft(booking.created_at);

  const isExpired = secondsLeft === 0 || result?.code === 'BOOKING_EXPIRED' || booking.status === 'EXPIRED';
  const payLabel = `Pay ${formatMoney(booking.price_paid, booking.currency, { decimals: 0 })}`;

  function updateField(name, value) {
    setCard({ ...card, [name]: value });
  }

  async function handlePaySubmit(event) {
    event.preventDefault();
    const errors = validateCard(card);
    setFieldErrors(errors);
    if (Object.keys(errors).length > 0) return;

    setIsPaying(true);
    setResult(null);
    try {
      await payBooking(booking.booking_reference, card);
      setResult({ code: 'PAID' });
      setCard(EMPTY_CARD);
      setTimeout(() => navigate(`/tickets/${booking.booking_reference}`), 1200);
    } catch (error) {
      handlePayError(error);
    } finally {
      setIsPaying(false);
    }
  }

  function handlePayError(error) {
    if (error.code === 'BOOKING_ALREADY_PAID') {
      navigate(`/tickets/${booking.booking_reference}`);
      return;
    }
    if (error.code === 'INVALID_CARD') {
      setFieldErrors({ card_number: error.message });
      return;
    }
    // After a decline the fields stay filled, except the CVC (design/Screens.md 4).
    if (error.code === 'PAYMENT_DECLINED') {
      setCard({ ...card, cvc: '' });
    }
    setResult(error);
  }

  if (isExpired && result?.code !== 'PAID') {
    return (
      <Alert variant="info" title="This booking expired. Please start again." actions={<button type="button" className="wl-btn wl-btn--primary" onClick={onStartAgain}>Start again</button>}>
        Unpaid bookings keep their places for {PAYMENT_HOLD_MINUTES} minutes only.
      </Alert>
    );
  }

  const minutes = Math.floor(secondsLeft / 60);
  const seconds = String(secondsLeft % 60).padStart(2, '0');

  return (
    <form className="wl-stack wl-pay-form" onSubmit={handlePaySubmit} noValidate>
      <h2>Pay for your booking</h2>
      <Alert variant="demo" title="Demo payment – no real money is charged.">
        Test cards: <code>4242 4242 4242 4242</code> (success), <code>4000 0000 0000 0002</code> (declined)
      </Alert>

      <PaymentResult result={result} />

      <CardField id="card_holder" label="Card holder" value={card.card_holder} error={fieldErrors.card_holder} disabled={isPaying}
        onChange={(value) => updateField('card_holder', value)} autoComplete="cc-name" />
      <CardField id="card_number" label="Card number" value={card.card_number} error={fieldErrors.card_number} disabled={isPaying} withIcon
        onChange={(value) => updateField('card_number', formatCardNumber(value))} autoComplete="cc-number" inputMode="numeric" placeholder="1234 5678 9012 3456" />
      <div className="wl-form-row">
        <CardField id="expiry" label="Expiry (MM/YY)" value={card.expiry} error={fieldErrors.expiry} disabled={isPaying}
          onChange={(value) => updateField('expiry', formatExpiry(value))} autoComplete="cc-exp" inputMode="numeric" placeholder="12/28" />
        <CardField id="cvc" label="CVC" value={card.cvc} error={fieldErrors.cvc} disabled={isPaying}
          onChange={(value) => updateField('cvc', value.replace(/\D/g, '').slice(0, 4))} autoComplete="cc-csc" inputMode="numeric" placeholder="123" />
      </div>

      <button type="submit" className={`wl-btn wl-btn--cta wl-btn--lg wl-btn--block${isPaying ? ' is-loading' : ''}`} aria-busy={isPaying} disabled={result?.code === 'PAID'}>
        {isPaying ? 'Processing payment' : result?.code === 'PAYMENT_DECLINED' ? 'Try again' : payLabel}
      </button>
      <p className="wl-hint" aria-live="off">Booking held for {minutes}:{seconds}</p>
    </form>
  );
}

function PaymentResult({ result }) {
  if (!result) return null;

  switch (result.code) {
    case 'PAID':
      return <Alert variant="success" title="Payment received">Your ticket is ready.</Alert>;
    case 'PAYMENT_DECLINED':
      return <Alert variant="danger" title="Payment declined">Your card was declined. Check the details or try another card.</Alert>;
    case 'PAYMENT_UNAVAILABLE':
      return (
        <Alert variant="warning" title="Payment service is busy" actions={<Link to="/my/bookings">Pay later from My bookings</Link>}>
          Please try again in a minute. Your booking is saved.
        </Alert>
      );
    default:
      return <ErrorAlert error={result} />;
  }
}

// One labelled input with its error text linked by aria-describedby.
function CardField({ id, label, value, error, onChange, withIcon = false, ...inputProps }) {
  const input = (
    <input
      className="wl-input"
      id={id}
      value={value}
      onChange={(event) => onChange(event.target.value)}
      aria-invalid={error ? 'true' : undefined}
      aria-describedby={error ? `${id}-error` : undefined}
      {...inputProps}
    />
  );

  return (
    <div className={`wl-field${error ? ' is-invalid' : ''}`}>
      <label className="wl-label" htmlFor={id}>{label}</label>
      {withIcon ? <div className="wl-input-icon"><CreditCard aria-hidden="true" />{input}</div> : input}
      {error && <p className="wl-error-text" id={`${id}-error`}>{error}</p>}
    </div>
  );
}
