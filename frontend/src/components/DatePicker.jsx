import { useState } from 'react';
import { addDays, parkToday } from '../utils/format';
import { MAX_BOOKING_DAYS_AHEAD } from '../utils/config';

// Month calendar for booking step 2 (design/components/DatePicker).
// Only today (park time) up to 90 days ahead can be picked. Weeks start on Monday.

const WEEKDAYS = ['Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa', 'Su'];

function monthStart(parkDate) {
  return `${parkDate.slice(0, 7)}-01`;
}

function shiftMonth(firstOfMonth, months) {
  const date = new Date(`${firstOfMonth}T12:00:00Z`);
  date.setUTCMonth(date.getUTCMonth() + months);

  return date.toISOString().slice(0, 10);
}

// The days of one month, with empty places before day 1 so it starts on the right weekday.
function monthCells(firstOfMonth) {
  const first = new Date(`${firstOfMonth}T12:00:00Z`);
  const emptyBefore = (first.getUTCDay() + 6) % 7;
  const daysInMonth = new Date(Date.UTC(first.getUTCFullYear(), first.getUTCMonth() + 1, 0)).getUTCDate();

  const cells = Array.from({ length: emptyBefore }, () => null);
  for (let day = 1; day <= daysInMonth; day++) {
    cells.push(`${firstOfMonth.slice(0, 8)}${String(day).padStart(2, '0')}`);
  }

  return cells;
}

function monthTitle(firstOfMonth) {
  return new Date(`${firstOfMonth}T12:00:00Z`).toLocaleDateString('en-GB', { month: 'long', year: 'numeric', timeZone: 'UTC' });
}

export default function DatePicker({ selectedDate, onSelect }) {
  const today = parkToday();
  const lastDay = addDays(today, MAX_BOOKING_DAYS_AHEAD);
  const [shownMonth, setShownMonth] = useState(monthStart(selectedDate || today));

  const canGoBack = shownMonth > monthStart(today);
  const canGoForward = shownMonth < monthStart(lastDay);

  return (
    <div className="wl-calendar">
      <div className="wl-calendar__head">
        <button type="button" className="wl-calendar__nav" aria-label="Previous month" disabled={!canGoBack} onClick={() => setShownMonth(shiftMonth(shownMonth, -1))}>‹</button>
        <span className="wl-calendar__month" aria-live="polite">{monthTitle(shownMonth)}</span>
        <button type="button" className="wl-calendar__nav" aria-label="Next month" disabled={!canGoForward} onClick={() => setShownMonth(shiftMonth(shownMonth, 1))}>›</button>
      </div>
      <div className="wl-calendar__grid">
        {WEEKDAYS.map((weekday) => <span key={weekday} className="wl-calendar__dow">{weekday}</span>)}
        {monthCells(shownMonth).map((date, index) => {
          if (date === null) return <span key={`empty-${index}`} />;

          const isAvailable = date >= today && date <= lastDay;
          const dayNumber = Number(date.slice(8));

          return (
            <button
              key={date}
              type="button"
              className={`wl-calendar__day${date === today ? ' is-today' : ''}`}
              disabled={!isAvailable}
              aria-selected={date === selectedDate}
              aria-label={`${dayNumber} ${monthTitle(shownMonth).split(' ')[0]}${isAvailable ? '' : ', unavailable'}`}
              onClick={() => onSelect(date)}
            >
              {dayNumber}
            </button>
          );
        })}
      </div>
    </div>
  );
}
