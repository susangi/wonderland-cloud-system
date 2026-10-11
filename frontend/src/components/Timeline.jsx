import { DoorOpen, FerrisWheel } from 'lucide-react';
import { formatLongDate, formatTime, guestText, tierNameFromCode, toParkDate } from '../utils/format';
import { categoryLabel } from '../utils/status';

// My visits timeline (design/components/Timeline): one group per visit day, newest day first,
// and inside a day oldest first so it reads like the day happened.

function groupByParkDay(visits) {
  const days = {};

  visits.forEach((visit) => {
    const parkDay = toParkDate(visit.occurred_at);
    days[parkDay] = days[parkDay] || [];
    days[parkDay].push(visit);
  });

  return Object.keys(days)
    .sort()
    .reverse()
    .map((parkDay) => ({
      parkDay,
      items: days[parkDay].sort((first, second) => first.occurred_at.localeCompare(second.occurred_at)),
    }));
}

// categoryById: { 3: 'water', ... } from the attractions list, for the ride meta line.
export default function Timeline({ visits, categoryById = {} }) {
  return (
    <div className="wl-card">
      {groupByParkDay(visits).map(({ parkDay, items }, index) => (
        <section key={parkDay}>
          <h2 className="wl-timeline-day" style={index === 0 ? { marginTop: 0 } : undefined}>{formatLongDate(parkDay)}</h2>
          <ol className="wl-timeline">
            {items.map((visit, itemIndex) => (
              <TimelineItem key={`${visit.occurred_at}-${itemIndex}`} visit={visit} category={categoryById[visit.attraction_id]} />
            ))}
          </ol>
        </section>
      ))}
    </div>
  );
}

function TimelineItem({ visit, category }) {
  const isGate = visit.event_type === 'GATE_CHECKIN';
  const Icon = isGate ? DoorOpen : FerrisWheel;
  // A ride name like "Unknown (id 5)" is shown as it is: it is honest and rare.
  const title = isGate ? 'Gate check-in' : visit.attraction_name;
  const meta = isGate
    ? `${tierNameFromCode(visit.tier_code)} ticket · ${guestText(visit.guests)}`
    : category ? categoryLabel(category) : `${tierNameFromCode(visit.tier_code)} ticket`;

  return (
    <li className={isGate ? 'is-gate' : undefined}>
      <span className="wl-timeline__dot"><Icon aria-hidden="true" /></span>
      <div>
        <div className="wl-timeline__title">{title}</div>
        <div className="wl-timeline__meta">{meta}</div>
      </div>
      <span className="wl-timeline__time">{formatTime(visit.occurred_at)}</span>
    </li>
  );
}
