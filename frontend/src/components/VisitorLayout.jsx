import { Link, Outlet } from 'react-router-dom';
import TopNav from './TopNav';

// Shell for visitor pages: skip link, top bar, page content, slim footer (design/Screens.md "Shared shell").
export default function VisitorLayout() {
  return (
    <div className="wl-app">
      <a className="wl-skip-link" href="#content">Skip to content</a>
      <TopNav />
      <main id="content" className="wl-page" tabIndex={-1}>
        <Outlet />
      </main>
      <footer className="wl-footer wl-on-dark wl-no-print">
        <div className="wl-container">
          <span>© Wonderland — a fictional park for a university project</span>
          <span>
            <Link to="/attractions">Attractions</Link>
            <Link to="/book">Book</Link>
          </span>
        </div>
      </footer>
    </div>
  );
}
