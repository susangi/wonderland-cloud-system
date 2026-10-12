import { Ruler, User } from 'lucide-react';
import ThrillMeter from './ThrillMeter';
import { categoryLabel } from '../utils/status';
import { attractionPhoto } from '../utils/attractionPhotos';

// One ride or activity (design/components/AttractionCard). No "Book" button: rides are not booked one by one.
// The art band shows a photo when we have one, otherwise the coloured circles.
export default function AttractionCard({ attraction }) {
  const photo = attractionPhoto(attraction.name);

  return (
    <article className="wl-card wl-attraction">
      <div className="wl-attraction__art" data-category={attraction.category} aria-hidden="true">
        {photo && <img className="wl-attraction__photo" src={photo} alt="" loading="lazy" />}
      </div>
      <div className="wl-attraction__body">
        <div className="wl-attraction__head">
          <h3 className="wl-attraction__title">{attraction.name}</h3>
          <span className={`wl-badge wl-badge--${attraction.category}`}>{categoryLabel(attraction.category)}</span>
        </div>
        <ThrillMeter level={attraction.thrill_level} />
        <p className="wl-attraction__desc">{attraction.description}</p>
        <SuitabilityBadges minAge={attraction.min_age} minHeight={attraction.min_height_cm} />
      </div>
    </article>
  );
}

// Age 0 = "All ages". Height 0 = no height badge (design/components/Badge).
function SuitabilityBadges({ minAge, minHeight }) {
  return (
    <div className="wl-attraction__suit">
      <span className="wl-badge wl-badge--outline">
        <User aria-hidden="true" />
        {minAge > 0 ? `Age ${minAge}+` : 'All ages'}
      </span>
      {minHeight > 0 && (
        <span className="wl-badge wl-badge--outline">
          <Ruler aria-hidden="true" />
          Min height {minHeight} cm
        </span>
      )}
    </div>
  );
}
