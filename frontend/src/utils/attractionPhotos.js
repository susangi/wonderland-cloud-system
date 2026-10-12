import thunderCoaster from '../assets/images/thunder-coaster.webp';
import dragonLoop from '../assets/images/dragon-loop.webp';
import splashMountain from '../assets/images/splash-mountain.webp';
import lazyRiver from '../assets/images/lazy-river.webp';
import teacupTwirl from '../assets/images/teacup-twirl.webp';
import laserMaze from '../assets/images/laser-maze.webp';
import pirateShip from '../assets/images/pirate-ship.webp';
import bumperCars from '../assets/images/bumper-cars.webp';

// Pictures for the attraction cards, matched by ride name (the API has no image field).
// Vite bundles these files, so they work the same on the laptop and on Amplify.
// Some are photos, some are drawings made for this project in the brand colours (SVG sources in assets/images/source).
// A ride without a picture keeps the circle art from the design system.
const PHOTOS_BY_NAME = {
  'Thunder Coaster': thunderCoaster,
  'Dragon Loop': dragonLoop,
  'Splash Mountain': splashMountain,
  'Lazy River': lazyRiver,
  'Teacup Twirl': teacupTwirl,
  'Laser Maze': laserMaze,
  'Pirate Ship': pirateShip,
  'Bumper Cars': bumperCars,
};

export function attractionPhoto(attractionName) {
  return PHOTOS_BY_NAME[attractionName] || null;
}
