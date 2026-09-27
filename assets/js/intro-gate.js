/* Runs synchronously in <head> (tiny) so the intro never flashes when it should not play:
   no JS → .no-js hides it; already seen this session or reduced motion → .intro-seen. */
(function (d) {
  var root = d.documentElement;
  root.className = root.className.replace('no-js', 'js');
  var seen = false;
  try { seen = sessionStorage.getItem('garage_intro_seen') === '1'; } catch (e) { seen = true; }
  var reduced = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  if (seen || reduced) root.className += ' intro-seen';
})(document);
