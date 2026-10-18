(function(){
'use strict';
const root=document.querySelector('[data-mtn-home-search]');
if(!root||!window.MTNHomeSearch)return;
const form=root.querySelector('form'),query=form.querySelector('[name="q"]'),kind=form.querySelector('[name="kind"]');
const status=root.querySelector('[data-search-status]'),results=root.querySelector('[data-search-results]');
let items=[];
const normalize=s=>String(s||'').toLocaleLowerCase('fa').replace(/[\u200c\s]+/g,' ').trim();
function addResult(item,type){
 const card=document.createElement('a');card.className='mtn-search-result';
 const tag=document.createElement('span');tag.className='mtn-result-type';tag.textContent=type;
 const title=document.createElement('strong');title.textContent=item.name||'بدون عنوان';
 const detail=document.createElement('small');detail.textContent=[item.cafe_name,item.location_path,item.event_date,item.event_start_time].filter(Boolean).join(' · ')||item.description||'برای مشاهده جزئیات باز کن';
 let url;try{url=new URL(item.url,window.location.origin);if(url.origin!==window.location.origin)return;}catch(e){return;}
 card.href=url.href;card.append(tag,title,detail);results.append(card);
}
function render(){
 const q=normalize(query.value),selected=kind.value;
 const filtered=items.filter(item=>(selected==='all'||item.type===selected)&&(!q||normalize([item.name,item.cafe_name,item.description,item.location_path,item.event_date].join(' ')).includes(q)));
 results.removeAttribute('aria-busy');results.replaceChildren();filtered.slice(0,12).forEach(item=>addResult(item,item.type==='event'?'برنامه زنده':(item.type==='artist'?'هنرمند':'کافه')));
 status.dataset.state='';status.textContent=filtered.length?('نمایش '+Math.min(filtered.length,12)+' نتیجه از '+filtered.length+' مورد'):'نتیجه‌ای پیدا نشد؛ نام کافه، هنرمند یا شهر دیگری را امتحان کن.';
}
status.textContent='در حال دریافت کافه‌ها و برنامه‌ها…';
for(let i=0;i<3;i++){const sk=document.createElement('span');sk.className='mtn-skeleton mtn-skeleton-card';sk.setAttribute('aria-hidden','true');results.append(sk);}
results.setAttribute('aria-busy','true');
fetch(MTNHomeSearch.catalogUrl,{headers:{Accept:'application/json'},credentials:'same-origin'}).then(response=>{if(!response.ok)throw new Error('catalog');return response.json();}).then(data=>{
 items=[...(data.cafes||[]).map(item=>({...item,type:'cafe'})),...(data.events||[]).map(item=>({...item,type:'event'})),...(data.artists||[]).map(item=>({...item,type:'artist'}))];
 status.textContent=items.length?'نام کافه، هنرمند، برنامه یا شهر را جست‌وجو کن.':'هنوز کافه یا برنامه‌ای برای نمایش ثبت نشده است.';render();
}).catch(()=>{results.removeAttribute('aria-busy');results.replaceChildren();status.dataset.state='error';status.textContent='بارگذاری جست‌وجو ناموفق بود. از آرشیو کافه‌ها یا برنامه‌ها استفاده کن.';});
form.addEventListener('submit',event=>{event.preventDefault();render();});
query.addEventListener('input',render);kind.addEventListener('change',render);
})();