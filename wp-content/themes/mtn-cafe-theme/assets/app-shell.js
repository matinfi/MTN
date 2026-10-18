(function(){
'use strict';
const shell=document.querySelector('[data-mtn-app-shell]');
if(!shell)return;
const sideToggle=document.querySelector('[data-mtn-sidebar-toggle]');
const mobileToggle=document.querySelector('[data-mtn-mobile-toggle]');
const sidebar=document.getElementById('mtn-sidebar');
const storageKey='mtn-sidebar-collapsed';
function mobile(){return window.matchMedia('(max-width: 700px)').matches;}
function setMobile(open){
 shell.classList.toggle('is-mobile-menu-open',open);
 if(mobileToggle)mobileToggle.setAttribute('aria-expanded',open?'true':'false');
 if(sidebar)sidebar.setAttribute('aria-hidden',mobile()&&!open?'true':'false');
 if(open){const first=sidebar&&sidebar.querySelector('a,button');if(first)first.focus();}
}
if(sideToggle){
 try{if(!mobile()&&localStorage.getItem(storageKey)==='1')shell.classList.add('is-sidebar-collapsed');}catch(e){}
 const initiallyCollapsed=shell.classList.contains('is-sidebar-collapsed');
 sideToggle.setAttribute('aria-expanded',initiallyCollapsed?'false':'true');
 sideToggle.setAttribute('aria-label',initiallyCollapsed?'باز کردن منو':'جمع کردن منو');
 const initialLabel=sideToggle.querySelector('.mtn-nav-text');
 if(initialLabel)initialLabel.textContent=initiallyCollapsed?'باز کردن منو':'جمع کردن منو';
 sideToggle.addEventListener('click',function(){
  const collapsed=shell.classList.toggle('is-sidebar-collapsed');
  sideToggle.setAttribute('aria-expanded',collapsed?'false':'true');
  sideToggle.setAttribute('aria-label',collapsed?'باز کردن منو':'جمع کردن منو');
  const label=sideToggle.querySelector('.mtn-nav-text');
  if(label)label.textContent=collapsed?'باز کردن منو':'جمع کردن منو';
  try{localStorage.setItem(storageKey,collapsed?'1':'0');}catch(e){}
 });
}
if(mobileToggle)mobileToggle.addEventListener('click',function(){setMobile(!shell.classList.contains('is-mobile-menu-open'));});
document.addEventListener('keydown',function(e){if(e.key==='Escape'&&shell.classList.contains('is-mobile-menu-open')){setMobile(false);if(mobileToggle)mobileToggle.focus();}});
document.addEventListener('pointerdown',function(e){if(!shell.classList.contains('is-mobile-menu-open'))return;if(sidebar&&!sidebar.contains(e.target)&&mobileToggle&&!mobileToggle.contains(e.target)){setMobile(false);}});
if(sidebar){sidebar.addEventListener('click',function(e){if(mobile()&&e.target.closest('a'))setMobile(false);});}
window.addEventListener('resize',function(){if(!mobile()){shell.classList.remove('is-mobile-menu-open');if(sidebar)sidebar.removeAttribute('aria-hidden');}else if(!shell.classList.contains('is-mobile-menu-open')&&sidebar){sidebar.setAttribute('aria-hidden','true');}});
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

(function(){
'use strict';
const cfg=window.MTNApp||{};
const region=document.querySelector('[data-mtn-toasts]');
function toast(message,type){
 if(!region||!message)return;
 const item=document.createElement('div');
 item.className='mtn-toast'+(type?' is-'+type:'');
 item.textContent=message;
 region.append(item);
 window.setTimeout(function(){item.classList.add('is-leaving');window.setTimeout(function(){item.remove();},260);},3600);
}
window.MTNApp=Object.assign(window.MTNApp||{},{toast:toast});
const msg=cfg.messages||{};
window.addEventListener('offline',function(){toast(msg.offline,'warning');document.documentElement.classList.add('is-offline');});
window.addEventListener('online',function(){toast(msg.online,'success');document.documentElement.classList.remove('is-offline');});
if(!navigator.onLine)document.documentElement.classList.add('is-offline');

// Installable web app.
if(cfg.pwa&&'serviceWorker' in navigator&&window.isSecureContext){
 window.addEventListener('load',function(){
  navigator.serviceWorker.register(cfg.swUrl,{scope:cfg.scope}).catch(function(){});
 });
}
const installButton=document.querySelector('[data-mtn-install]');
let deferred=null;
window.addEventListener('beforeinstallprompt',function(event){
 event.preventDefault();deferred=event;
 if(installButton)installButton.hidden=false;
});
if(installButton){
 installButton.addEventListener('click',function(){
  if(!deferred)return;
  deferred.prompt();
  deferred.userChoice.finally(function(){deferred=null;installButton.hidden=true;});
 });
}
window.addEventListener('appinstalled',function(){if(installButton)installButton.hidden=true;toast(msg.installed,'success');});
if(window.matchMedia&&window.matchMedia('(display-mode: standalone)').matches)document.documentElement.classList.add('is-standalone');

// Press feedback on touch devices and in-flight state for forms.
document.addEventListener('submit',function(event){
 const form=event.target;
 if(!form||!form.matches||event.defaultPrevented)return;
 const button=form.querySelector('button[type="submit"],input[type="submit"]');
 if(button&&!button.disabled)window.setTimeout(function(){button.setAttribute('aria-busy','true');},0);
});
})();
