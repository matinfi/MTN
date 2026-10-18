(function(){
'use strict';
const shell=document.querySelector('[data-mtn-app-shell]');
if(!shell)return;
const sideToggle=shell.querySelector('[data-mtn-sidebar-toggle]');
const mobileToggle=shell.querySelector('[data-mtn-mobile-toggle]');
const sidebar=shell.querySelector('#mtn-sidebar');
const storageKey='mtn-sidebar-collapsed';
function mobile(){return window.matchMedia('(max-width: 700px)').matches;}
function setSidebarCollapsed(collapsed){
 shell.classList.toggle('is-sidebar-collapsed',collapsed);
 if(sideToggle){
  sideToggle.setAttribute('aria-expanded',collapsed?'false':'true');
  sideToggle.setAttribute('aria-label',collapsed?'باز کردن منو':'جمع کردن منو');
  sideToggle.setAttribute('title',collapsed?'باز کردن منو':'جمع کردن منو');
  const label=sideToggle.querySelector('.mtn-nav-text');
  if(label)label.textContent=collapsed?'باز کردن منو':'جمع کردن منو';
 }
 try{localStorage.setItem(storageKey,collapsed?'1':'0');}catch(e){}
}
function setMobile(open){
 shell.classList.toggle('is-mobile-menu-open',open);
 if(mobileToggle)mobileToggle.setAttribute('aria-expanded',open?'true':'false');
 if(sidebar)sidebar.setAttribute('aria-hidden',mobile()&&!open?'true':'false');
 if(open){const first=sidebar&&sidebar.querySelector('a,button');if(first)first.focus();}
}
let initiallyCollapsed=false;
try{initiallyCollapsed=!mobile()&&localStorage.getItem(storageKey)==='1';}catch(e){}
if(sideToggle){
 setSidebarCollapsed(initiallyCollapsed);
 sideToggle.addEventListener('click',function(e){
  e.preventDefault();
  e.stopPropagation();
  setSidebarCollapsed(!shell.classList.contains('is-sidebar-collapsed'));
 });
}
if(mobileToggle)mobileToggle.addEventListener('click',function(e){e.preventDefault();e.stopPropagation();setMobile(!shell.classList.contains('is-mobile-menu-open'));});
document.addEventListener('keydown',function(e){if(e.key==='Escape'&&shell.classList.contains('is-mobile-menu-open')){setMobile(false);if(mobileToggle)mobileToggle.focus();}});
document.addEventListener('pointerdown',function(e){if(!shell.classList.contains('is-mobile-menu-open'))return;if(sidebar&&!sidebar.contains(e.target)&&mobileToggle&&!mobileToggle.contains(e.target)){setMobile(false);}});
if(sidebar){sidebar.addEventListener('click',function(e){if(mobile()&&e.target.closest('a'))setMobile(false);});}
window.addEventListener('resize',function(){if(!mobile()){shell.classList.remove('is-mobile-menu-open');if(sidebar)sidebar.removeAttribute('aria-hidden');}else{shell.classList.remove('is-sidebar-collapsed');if(sidebar&&!shell.classList.contains('is-mobile-menu-open'))sidebar.setAttribute('aria-hidden','true');}});
if(mobile()&&sidebar)sidebar.setAttribute('aria-hidden','true');
const themeToggle=document.querySelector('[data-mtn-theme-toggle]');
const themeKey='mtn-color-theme';
function setTheme(theme){
 document.documentElement.setAttribute('data-mtn-theme',theme);
 if(themeToggle){
  const light=theme==='light';
  themeToggle.setAttribute('aria-pressed',light?'true':'false');
  themeToggle.setAttribute('aria-label',light?'فعال‌سازی پوسته تاریک':'فعال‌سازی پوسته روشن');
  const label=themeToggle.querySelector('.mtn-theme-toggle-label');
  if(label)label.textContent=light?'پوسته تاریک':'پوسته روشن';
 }
}
let initialTheme=document.documentElement.getAttribute('data-mtn-default-theme')==='light'?'light':'dark';
try{const savedTheme=localStorage.getItem(themeKey);if(savedTheme==='light'||savedTheme==='dark')initialTheme=savedTheme;}catch(e){}
setTheme(initialTheme);
if(themeToggle)themeToggle.addEventListener('click',function(){
 const next=document.documentElement.getAttribute('data-mtn-theme')==='light'?'dark':'light';
 setTheme(next);
 try{localStorage.setItem(themeKey,next);}catch(e){}
});
})();