import { Link } from 'react-router-dom';

// Navy panel at the top of Home with the brand circles (design/components/Hero).
export default function Hero() {
  return (
    <section className="wl-hero wl-on-dark">
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
