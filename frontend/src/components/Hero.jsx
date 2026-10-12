import { Link } from 'react-router-dom';
import heroPhoto from '../assets/images/hero.webp';

// Navy panel at the top of Home (design/components/Hero).
// The photo sits in the right half and the text stays on navy, as the Hero guide asks.
// The brand circles stay as a small accent at the corner of the photo.
export default function Hero() {
  return (
    <section className="wl-hero wl-hero--photo wl-on-dark">
      <img className="wl-hero__photo" src={heroPhoto} alt="Riders with their hands up on a rollercoaster" />
      <svg className="wl-hero__shapes" viewBox="0 0 420 420" aria-hidden="true">
        <circle cx="270" cy="150" r="150" style={{ fill: 'var(--teal-600)' }} />
        <circle cx="150" cy="250" r="90" style={{ fill: 'var(--coral-500)' }} />
        <circle cx="330" cy="320" r="48" style={{ fill: 'var(--amber-400)' }} />
      </svg>
      <h1>A day you&apos;ll talk about for years</h1>
      <p>
        Coasters, water rides, kids&apos; favourites and games for everyone — book your time slot online and skip the
        ticket queue.
      </p>
      <div className="wl-hero__actions">
        <Link className="wl-btn wl-btn--cta wl-btn--lg" to="/book">Book tickets</Link>
        <Link className="wl-btn wl-btn--secondary-dark wl-btn--lg" to="/attractions">Explore rides</Link>
      </div>
    </section>
  );
}
