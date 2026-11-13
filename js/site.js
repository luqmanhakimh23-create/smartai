const html=document.documentElement;
const saved=localStorage.getItem('theme');
if(saved)html.setAttribute('data-theme',saved);
document.getElementById('theme-toggle').addEventListener('click',()=>{
  const n=html.getAttribute('data-theme')==='dark'?'light':'dark';
  html.setAttribute('data-theme',n);
  localStorage.setItem('theme',n);
});
const hamburger=document.getElementById('hamburger'),mobileMenu=document.getElementById('mobile-menu');
hamburger.addEventListener('click',()=>{const open=mobileMenu.classList.toggle('open');document.body.style.overflow=open?'hidden':'';});
function closeMenu(){mobileMenu.classList.remove('open');document.body.style.overflow='';}
function toggleFaq(btn){const item=btn.parentElement;const was=item.classList.contains('active');document.querySelectorAll('.faq-item').forEach(i=>i.classList.remove('active'));if(!was)item.classList.add('active');}
const nav=document.querySelector('.navbar');
window.addEventListener('scroll',()=>{if(nav)nav.classList.toggle('scrolled',window.scrollY>40);},{passive:true});

/* ===== Animasi pop pada card: saat halaman dimuat & saat card diklik ===== */
(function(){
  if(window.matchMedia&&matchMedia('(prefers-reduced-motion: reduce)').matches)return;
  var LOAD='.why-card,.eco-card,.perf-card,.testi-card,.step,.feature-bar-item,.faq-item,.cta-section .form-card';
  var CLICK=LOAD+',.monitor-card,.stat-box';
  var cards=[].slice.call(document.querySelectorAll(LOAD));
  function popIn(el){
    if(!el.classList.contains('pop-card'))return;
    var i=[].indexOf.call(el.parentNode.children,el)%6;
    el.classList.remove('pop-card');
    if(el.animate)el.animate([
      {opacity:0,transform:'scale(.78) translateY(24px)'},
      {opacity:1,transform:'scale(1.05) translateY(0)',offset:.6},
      {opacity:1,transform:'scale(1)'}
    ],{duration:650,delay:i*80,easing:'cubic-bezier(.22,1,.36,1)',fill:'backwards'});
  }
  cards.forEach(function(el){el.classList.remove('reveal');el.classList.add('pop-card');});
  if('IntersectionObserver' in window){
    var io=new IntersectionObserver(function(es){
      es.forEach(function(e){if(e.isIntersecting){popIn(e.target);io.unobserve(e.target);}});
    },{threshold:.1});
    cards.forEach(function(el){io.observe(el);});
  }else{cards.forEach(popIn);}
  setTimeout(function(){cards.forEach(popIn);},3500);
  // pop saat diklik
  document.addEventListener('click',function(e){
    var el=e.target.closest&&e.target.closest(CLICK);
    if(!el||!el.animate)return;
    el.animate([
      {transform:'scale(1)'},
      {transform:'scale(.93)',offset:.25},
      {transform:'scale(1.06)',offset:.65},
      {transform:'scale(1)'}
    ],{duration:450,easing:'ease-out'});
  });
})();
