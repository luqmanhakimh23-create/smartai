(function(){
  var hero=document.querySelector('.hero');
  if(hero && !document.querySelector('.hero-orbs')){
    var o=document.createElement('div');
    o.className='hero-orbs';o.setAttribute('aria-hidden','true');
    o.innerHTML='<div class="orb orb-1"></div><div class="orb orb-2"></div>';
    hero.insertBefore(o,hero.firstChild);
  }
  var els=document.querySelectorAll('.reveal');
  els.forEach(function(el,i){
    if(el.closest('.hero-visual')||el.classList.contains('section-label')||el.classList.contains('section-title'))return;
    var m=i%5;if(m===1)el.classList.add('from-left');else if(m===2)el.classList.add('from-right');else if(m===3)el.classList.add('scale-up');
  });
  function show(el){el.classList.add('visible');}
  function animateCount(el){
    if(el.dataset.counted)return;el.dataset.counted='1';
    var raw=el.textContent.trim(),m=raw.match(/^([+$]?)([\d.,]+)(.*)$/);
    if(!m)return;
    var pre=m[1],ns=m[2].replace(/,/g,''),suf=m[3],target=parseFloat(ns);
    if(isNaN(target))return;
    var isInt=!ns.includes('.'),t0=performance.now();
    (function tick(now){
      var t=Math.min(1,(now-t0)/900),e=1-Math.pow(1-t,3),v=target*e;
      el.textContent=pre+(isInt?Math.round(v).toLocaleString('en-US'):v.toFixed((ns.split('.')[1]||'').length||2))+suf;
      if(t<1)requestAnimationFrame(tick);else{el.textContent=raw;el.classList.add('counted');}
    })(t0);
  }
  if('IntersectionObserver' in window){
    var obs=new IntersectionObserver(function(entries){
      entries.forEach(function(e){
        if(e.isIntersecting){
          show(e.target);obs.unobserve(e.target);
          e.target.querySelectorAll('.perf-stat .value').forEach(animateCount);
        }
      });
    },{threshold:0.1,rootMargin:'0px 0px -30px 0px'});
    els.forEach(function(el,i){el.style.transitionDelay=(i%7)*0.06+'s';obs.observe(el);});
  }else{els.forEach(show);}
  setTimeout(function(){document.querySelectorAll('.reveal:not(.visible)').forEach(show);},3500);
})();
