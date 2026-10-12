import { slotName } from '../utils/format';

// The four daily slots with places left (design/components/TimeSlot).
// Full slots and slots without room for this group stay visible but disabled, with the reason.
export default function TimeSlotList({ slots, guests, selectedSlotId, onSelect }) {
  return (
    <div className="wl-slots" role="group" aria-label="Time slot">
      {slots.map((slot) => {
        const bookedPercent = Math.round(((slot.capacity - slot.remaining) / slot.capacity) * 100);
        const hasRoom = slot.remaining >= guests;
        const isLow = slot.remaining < guests * 5 || slot.remaining < 20;

        let placesText = `${slot.remaining} left`;
        if (slot.remaining <= 0) placesText = 'Full';
        else if (!hasRoom) placesText = `Only ${slot.remaining} left`;

        return (
          <button
            key={slot.id}
            type="button"
            className={`wl-slot${isLow && hasRoom ? ' is-low' : ''}`}
            aria-pressed={slot.id === selectedSlotId}
            disabled={!hasRoom}
            onClick={() => onSelect(slot)}
          >
            <span className="wl-slot__name">{slotName(slot.label)}</span>
            <span className="wl-slot__time">{slot.start_time}–{slot.end_time}</span>
            <span className="wl-slot__bar" aria-hidden="true"><span style={{ width: `${bookedPercent}%` }} /></span>
            <span className="wl-slot__cap"><b>{placesText}</b><span>of {slot.capacity}</span></span>
          </button>
        );
      })}
    </div>
  );
}
