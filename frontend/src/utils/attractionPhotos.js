import thunderCoaster from '../assets/images/thunder-coaster.webp';
import dragonLoop from '../assets/images/dragon-loop.webp';
import splashMountain from '../assets/images/splash-mountain.webp';
import teacupTwirl from '../assets/images/teacup-twirl.webp';

// Photos for the attraction cards, matched by ride name (the API has no image field).
// Vite bundles these files, so they work the same on the laptop and on Amplify.
// Rides without a photo keep the circle art from the design system.
const PHOTOS_BY_NAME = {
  'Thunder Coaster': thunderCoaster,
  'Dragon Loop': dragonLoop,
  'Splash Mountain': splashMountain,
  'Teacup Twirl': teacupTwirl,
};

export function attractionPhoto(attractionName) {
  return PHOTOS_BY_NAME[attractionName] || null;
}
