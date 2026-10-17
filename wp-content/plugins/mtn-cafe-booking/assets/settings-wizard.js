(function(){
'use strict';
const form=document.querySelector('form[data-mtn-settings-wizard]');
if(!form)return;
const steps=[
 {title:'اطلاعات پایه',fields:['mtn_cb_brand_name','mtn_cb_currency','mtn_cb_booking_mode']},
 {title:'کارمزد و امور مالی',fields:['mtn_cb_commission_trigger_status','mtn_cb_default_commission','mtn_cb_reverse_commission_on_cancellation','mtn_cb_reverse_commission_on_no_show','mtn_cb_reverse_commission_on_event_cancel','mtn_cb_statement_due_days']},
 {title:'قوانین رزرو',fields:['mtn_cb_booking_expiry_minutes','mtn_cb_checkin_token_minutes','mtn_cb_booking_duration_minutes','mtn_cb_customer_cancel_cutoff_minutes']}
];
const rows=new Map();
steps.forEach((step,index)=>step.fields.forEach(name=>{const el=form.querySelector('[name="'+name+'"]');if(el){const tr=el.closest('tr');if(tr)rows.set(tr,index);}}));
let current=0;
const bar=document.createElement('ol');bar.className='mtn-wizard-steps';bar.setAttribute('aria-label','مراحل تنظیمات');
steps.forEach((step,i)=>{const li=document.createElement('li');li.textContent=(i+1)+' · '+step.title;li.dataset.step=String(i);bar.appendChild(li);});
const table=form.querySelector('.form-table');
if(table)table.before(bar);
const actions=document.createElement('div');actions.className='mtn-wizard-actions';
const back=document.createElement('button');back.type='button';back.className='button';back.textContent='مرحله قبل';
const next=document.createElement('button');next.type='button';next.className='button button-primary';next.textContent='مرحله بعد';
actions.append(back,next);form.appendChild(actions);
function render(){rows.forEach((step,row)=>{row.hidden=step!==current;});Array.from(bar.children).forEach((li,i)=>{li.classList.toggle('is-active',i===current);li.classList.toggle('is-complete',i<current);li.setAttribute('aria-current',i===current?'step':'false');});back.hidden=current===0;next.hidden=current===steps.length-1;const submit=form.querySelector('[type="submit"]');if(submit)submit.hidden=current!==steps.length-1;}
function valid(){const active=steps[current];for(const name of active.fields){const el=form.querySelector('[name="'+name+'"]');if(!el||el.type==='hidden'||el.disabled)continue;if(!el.checkValidity()){el.reportValidity();el.focus();return false;}if(el.type==='number'&&el.value!==''&&Number(el.value)<Number(el.min||-Infinity)){el.reportValidity();el.focus();return false;}}return true;}
back.addEventListener('click',()=>{if(current>0){current--;render();}});
next.addEventListener('click',()=>{if(!valid())return;if(current<steps.length-1){current++;render();}});
form.addEventListener('submit',e=>{if(current!==steps.length-1){e.preventDefault();if(valid()){current=steps.length-1;render();}}else if(!valid())e.preventDefault();});
render();
})();