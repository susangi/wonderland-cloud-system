import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { CalendarDays, FerrisWheel, Ticket } from 'lucide-react';
import Hero from '../components/Hero';
import Alert from '../components/Alert';
import AttractionCard from '../components/AttractionCard';
import TierCard, { bestValueTierId } from '../components/TierCard';
import { SkeletonCards } from '../components/LoadingState';
import { getTiers } from '../api/pricingApi';
import { getAttractions } from '../api/attractionApi';

// Home page (design/Screens.md section 1). If pricing is down, only the tier row is hidden.

const POPULAR_RIDE_NAMES = ['Thunder Coaster', 'Splash Mountain', 'Dragon Loop', 'Teacup Twirl'];

const THINGS_TO_KNOW = [
  { icon: CalendarDays, title: 'Book a time slot', text: 'Pick a day and a time online. Each slot has limited places, so the park never gets too crowded.' },
  { icon: Ticket, title: 'Show your QR at the gate', text: 'Your ticket is on your phone. One scan lets your whole group in.' },
  { icon: FerrisWheel, title: 'Ride as much as you like', text: 'Show the same QR code at each ride. Check age and height before you queue.' },
];

export default function HomePage() {
  const [tiers, setTiers] = useState([]);
  const [isTiersLoading, setIsTiersLoading] = useState(true);
  const [hasTiersError, setHasTiersError] = useState(false);
  const [popularRides, setPopularRides] = useState([]);

  useEffect(() => {
    getTiers()
      .then(setTiers)
      .catch(() => setHasTiersError(true))
      .finally(() => setIsTiersLoading(false));

    getAttractions()
      .then((attractions) => setPopularRides(pickPopularRides(attractions)))
      .catch(() => setPopularRides([]));
  }, []);

  const bestValueId = bestValueTierId(tiers);

  return (
    <div className="wl-container wl-stack" style={{ gap: 'var(--space-7)' }}>
      <Hero />

      <section className="wl-section" aria-labelledby="know-title">
        <h2 id="know-title">Three things to know</h2>
        <div className="wl-grid">
          {THINGS_TO_KNOW.map(({ icon: Icon, title, text }) => (
            <div key={title} className="wl-card wl-stack">
              <span className="wl-empty__art" style={{ width: 48, height: 48 }}><Icon aria-hidden="true" /></span>
              <h3>{title}</h3>
              <p className="wl-muted">{text}</p>
            </div>
          ))}
        </div>
      </section>

      <section className="wl-section" aria-labelledby="tiers-title">
        <h2 id="tiers-title">Ticket tiers</h2>
        {hasTiersError && <Alert variant="warning" title="Prices are unavailable right now">Please try again shortly. You can still browse the rides.</Alert>}
        {isTiersLoading && <SkeletonCards count={4} withArt={false} label="Loading ticket tiers…" />}
        {!isTiersLoading && !hasTiersError && (
          <div className="wl-tier-row">
            {tiers.map((tier) => (
              <Link key={tier.id} to={`/book?tier=${tier.code}`} aria-label={`Book ${tier.name} ticket`}>
                <TierCard tier={tier} isBestValue={tier.id === bestValueId} />
              </Link>
            ))}
          </div>
        )}
      </section>

      <section className="wl-section" aria-labelledby="rides-title">
        <h2 id="rides-title">Popular rides</h2>
        <div className="wl-grid wl-grid--4">
          {popularRides.map((attraction) => <AttractionCard key={attraction.id} attraction={attraction} />)}
        </div>
        <div>
          <Link className="wl-btn" to="/attractions">See all attractions</Link>
        </div>
      </section>
    </div>
  );
}

// The four favourites from the design, or simply the first four if they were renamed.
function pickPopularRides(attractions) {
  const favourites = POPULAR_RIDE_NAMES.map((name) => attractions.find((item) => item.name === name)).filter(Boolean);

  return favourites.length === 4 ? favourites : attractions.slice(0, 4);
}
