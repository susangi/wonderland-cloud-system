import { useState } from 'react';
import { NavLink, useNavigate } from 'react-router-dom';
import { Menu, X } from 'lucide-react';
import Logo from './Logo';
import { useAuth } from '../auth/AuthContext';

// Navy bar on every visitor page (design/components/TopNav).
// Under 768px the links move into a drawer that pushes the page down.
export default function TopNav() {
  const { isLoggedIn, isAdmin, logout } = useAuth();
  const [isMenuOpen, setIsMenuOpen] = useState(false);
  const navigate = useNavigate();

  const links = [
    { to: '/attractions', label: 'Attractions' },
    { to: '/book', label: 'Book' },
    ...(isLoggedIn ? [{ to: '/my/bookings', label: 'My bookings' }, { to: '/my/visits', label: 'My visits' }] : []),
    // Shown only to admins. The real protection is the server's 403.
    ...(isAdmin ? [{ to: '/admin', label: 'Admin' }] : []),
  ];

  async function handleLogout() {
    setIsMenuOpen(false);
    await logout();
    navigate('/');
  }

  // NavLink sets aria-current="page" on the current link (coral underline in the CSS).
  const linkItems = links.map((link) => (
    <li key={link.to}>
      <NavLink to={link.to} onClick={() => setIsMenuOpen(false)}>{link.label}</NavLink>
    </li>
  ));

  return (
    <header className="wl-topnav wl-on-dark">
      <div className="wl-container wl-topnav__inner">
        <Logo />
        <ul className="wl-topnav__links">
          {linkItems}
          <li style={{ marginLeft: 'var(--space-2)' }}>
            {isLoggedIn ? (
              <button type="button" className="wl-btn wl-btn--sm" onClick={handleLogout}>Log out</button>
            ) : (
              <NavLink className="wl-btn wl-btn--sm wl-btn--cta" to="/login">Log in</NavLink>
            )}
          </li>
        </ul>
        <button
          type="button"
          className="wl-topnav__menu"
          aria-label={isMenuOpen ? 'Close menu' : 'Menu'}
          aria-expanded={isMenuOpen}
          aria-controls="wl-mobile-menu"
          onClick={() => setIsMenuOpen(!isMenuOpen)}
        >
          {isMenuOpen ? <X aria-hidden="true" /> : <Menu aria-hidden="true" />}
        </button>
      </div>
      {isMenuOpen && (
        <nav id="wl-mobile-menu" className="wl-drawer" aria-label="Main">
          {links.map((link) => (
            <NavLink key={link.to} to={link.to} onClick={() => setIsMenuOpen(false)}>{link.label}</NavLink>
          ))}
          {isLoggedIn ? (
            <button type="button" onClick={handleLogout}>Log out</button>
          ) : (
            <NavLink to="/login" onClick={() => setIsMenuOpen(false)}>Log in</NavLink>
          )}
        </nav>
      )}
    </header>
  );
}
