import { PARK_TIMEZONE } from './config';

// Formatting helpers so money, dates and times look the same on every page (design README "Content").

// "USD 140.00" in tables and summaries, "USD 140" on tier cards and the pay button.
export function formatMoney(amount, currency = 'USD', { decimals = 2 } = {}) {
  const number = Number(amount || 0).toLocaleString('en-US', {
    minimumFractionDigits: decimals,
    maximumFractionDigits: decimals,
  });

  return `${currency} ${number}`;
}

export function formatCount(count) {
  return Number(count || 0).toLocaleString('en-US');
}

// A park date "2026-11-08" is a calendar day, so we read it as UTC noon to avoid timezone shifts.
function parkDateToDate(parkDate) {
  return new Date(`${parkDate}T12:00:00Z`);
}

// "Sun 8 Nov 2026"
export function formatDate(parkDate) {
  if (!parkDate) return '';
  const date = parkDateToDate(parkDate);
  const weekday = date.toLocaleDateString('en-GB', { weekday: 'short', timeZone: 'UTC' });
  const dayMonthYear = date.toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric', timeZone: 'UTC' });

  return `${weekday} ${dayMonthYear}`;
}

// "Sun 8 Nov"
export function formatShortDate(parkDate) {
  return formatDate(parkDate).replace(/ \d{4}$/, '');
}

// "Sunday 8 November 2026" for timeline day headings.
export function formatLongDate(parkDate) {
  const date = parkDateToDate(parkDate);
  const weekday = date.toLocaleDateString('en-GB', { weekday: 'long', timeZone: 'UTC' });
  const dayMonthYear = date.toLocaleDateString('en-GB', { day: 'numeric', month: 'long', year: 'numeric', timeZone: 'UTC' });

  return `${weekday} ${dayMonthYear}`;
}

// "10:32" in park time, from an ISO timestamp sent by the API (stored in UTC).
export function formatTime(isoTimestamp) {
  if (!isoTimestamp) return '';

  return new Date(isoTimestamp).toLocaleTimeString('en-GB', {
    hour: '2-digit',
    minute: '2-digit',
    hour12: false,
    timeZone: PARK_TIMEZONE,
  });
}

// The park date ("2026-11-08") of an ISO timestamp.
export function toParkDate(isoTimestamp) {
  return new Date(isoTimestamp).toLocaleDateString('en-CA', { timeZone: PARK_TIMEZONE });
}

// Today's date in the park, "2026-11-08".
export function parkToday() {
  return toParkDate(new Date().toISOString());
}

// "2026-11-08" plus some days, still as "YYYY-MM-DD".
export function addDays(parkDate, days) {
  const date = parkDateToDate(parkDate);
  date.setUTCDate(date.getUTCDate() + days);

  return date.toISOString().slice(0, 10);
}

// "Morning 09:00–12:00" -> "Morning"
export function slotName(slotLabel) {
  return (slotLabel || '').split(' ')[0];
}

// "FAMILY" -> "Family"
export function tierNameFromCode(tierCode) {
  if (!tierCode) return '';
  return tierCode.charAt(0) + tierCode.slice(1).toLowerCase();
}

// "4 guests" / "1 guest"
export function guestText(guests) {
  return `${guests} ${guests === 1 ? 'guest' : 'guests'}`;
}
