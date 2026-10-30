const menuToggle=document.getElementById('menuToggle');
const sidebar=document.getElementById('sidebar');
const sidebarScrim=document.getElementById('sidebarScrim');
function closeSidebar(){sidebar?.classList.remove('open');sidebarScrim?.classList.remove('open')}
menuToggle?.addEventListener('click',()=>{sidebar?.classList.toggle('open');sidebarScrim?.classList.toggle('open')});
sidebarScrim?.addEventListener('click',closeSidebar);

const classSelect=document.getElementById('class_id');
const classSummary={name:document.getElementById('selected-class'),group:document.getElementById('selected-subject-group'),section:document.getElementById('selected-section')};
function updateAdmissionClass(updateFee=false){
    const option=classSelect?.selectedOptions[0];
    if(!option||!option.value)return;
    if(classSummary.name)classSummary.name.textContent=option.dataset.className||option.textContent;
    if(classSummary.group)classSummary.group.textContent=option.dataset.subjectGroup||'—';
    if(classSummary.section)classSummary.section.textContent=option.dataset.section||'—';
    const fee=document.getElementById('monthly_fee');
    if(fee&&(updateFee||!fee.value))fee.value=option.dataset.monthlyFee||'';
}
classSelect?.addEventListener('change',()=>updateAdmissionClass(true));
updateAdmissionClass(false);
document.getElementById('photo')?.addEventListener('change',event=>{
    const file=event.target.files?.[0];
    const preview=document.getElementById('photoPreview');
    if(file&&preview){preview.src=URL.createObjectURL(file);preview.hidden=false;}
});

document.querySelectorAll('[data-filter-select]').forEach(input=>{
    const select=document.getElementById(input.dataset.filterSelect);
    if(!select)return;
    const label=input.closest('label');
    const scope=label||input.parentElement;
    const results=document.createElement('div');
    results.className='search-picker-results';
    results.id=`${select.id}-search-results`;
    results.setAttribute('role','listbox');
    results.hidden=true;
    input.insertAdjacentElement('afterend',results);
    input.classList.add('search-picker-input');
    input.setAttribute('role','combobox');
    input.setAttribute('aria-autocomplete','list');
    input.setAttribute('aria-controls',results.id);
    input.setAttribute('aria-expanded','false');
    const fieldLabel=document.querySelector(`label[for="${select.id}"]`)?.textContent.trim()||label?.childNodes[0]?.textContent?.trim();
    if(!input.getAttribute('aria-label'))input.setAttribute('aria-label',fieldLabel||'Search options');
    const wasRequired=select.required;
    select.required=false;
    input.required=wasRequired;
    const updateValidity=()=>input.setCustomValidity(wasRequired&&!select.value?'Choose an option from the matching results.':'');
    select.classList.add('search-picker-source');
    select.tabIndex=-1;
    select.setAttribute('aria-hidden','true');
    let activeIndex=-1;
    const options=()=>Array.from(select.options).filter(option=>option.value&&!option.hidden);
    if(select.value)input.value=select.selectedOptions[0]?.textContent.trim()||'';
    const close=()=>{results.hidden=true;input.setAttribute('aria-expanded','false');input.removeAttribute('aria-activedescendant');activeIndex=-1};
    const choose=option=>{
        select.value=option.value;
        input.value=option.dataset.queryValue||option.textContent.trim();
        updateValidity();
        close();
        select.dispatchEvent(new Event('change',{bubbles:true}));
    };
    const render=(term='')=>{
        const needle=term.trim().toLocaleLowerCase();
        const matches=options().filter(option=>option.textContent.toLocaleLowerCase().includes(needle))
            .sort((a,b)=>(a.dataset.sortKey||a.textContent).localeCompare(b.dataset.sortKey||b.textContent,undefined,{numeric:true,sensitivity:'base'}));
        results.replaceChildren();
        if(!matches.length){
            const empty=document.createElement('div');empty.className='search-picker-empty';empty.textContent='No matching options';results.append(empty);
        }else{
            for(const [index,option] of matches.entries()){
                const item=document.createElement('button');item.type='button';item.id=`${results.id}-option-${index}`;item.className='search-picker-option';item.setAttribute('role','option');item.setAttribute('aria-selected','false');item.textContent=option.textContent.trim();
                item.addEventListener('click',()=>choose(option));
                results.append(item);
            }
        }
        activeIndex=-1;results.hidden=false;input.setAttribute('aria-expanded','true');
    };
    const setActive=direction=>{
        const items=Array.from(results.querySelectorAll('.search-picker-option'));
        if(!items.length)return;
        if(activeIndex<0&&direction<0)activeIndex=0;
        activeIndex=(activeIndex+direction+items.length)%items.length;
        items.forEach((item,index)=>{item.classList.toggle('active',index===activeIndex);item.setAttribute('aria-selected',index===activeIndex?'true':'false')});
        input.setAttribute('aria-activedescendant',items[activeIndex].id);
        items[activeIndex].scrollIntoView({block:'nearest'});
    };
    input.addEventListener('focus',()=>render(''));
    input.addEventListener('input',()=>{
        if(select.value)select.value='';
        updateValidity();
        render(input.value);
    });
    input.addEventListener('keydown',event=>{
        if(event.key==='ArrowDown'){event.preventDefault();if(results.hidden)render(input.value);setActive(1)}
        else if(event.key==='ArrowUp'){event.preventDefault();setActive(-1)}
        else if(event.key==='Escape')close();
        else if(event.key==='Enter'&&!results.hidden){
            const items=results.querySelectorAll('.search-picker-option');
            const exact=options().find(option=>option.textContent.trim().toLocaleLowerCase()===input.value.trim().toLocaleLowerCase());
            if(activeIndex>=0){event.preventDefault();items[activeIndex].click()}
            else if(exact){event.preventDefault();choose(exact)}
            else if(items.length===1){event.preventDefault();items[0].click()}
        }
    });
    select.addEventListener('change',()=>{
        const selected=select.selectedOptions[0];
        if(selected?.value)input.value=selected.dataset.queryValue||selected.textContent.trim();
        updateValidity();
    });
    select.addEventListener('options-updated',()=>{if(!results.hidden)render(input.value)});
    updateValidity();
    document.addEventListener('pointerdown',event=>{if(!scope?.contains(event.target))close()});
});

const studentSelect=document.getElementById('student_id');
const invoiceSelect=document.getElementById('fee_invoice_id');
const feeAmount=document.getElementById('amount');
if(studentSelect&&invoiceSelect){
    const invoiceSearch=document.querySelector('[data-filter-select="fee_invoice_id"]');
    const initialInvoiceId=invoiceSelect.value;
    const filterStudentInvoices=()=>{
        const studentId=studentSelect.value;
        for(const option of invoiceSelect.options){
            if(!option.value)continue;
            option.hidden=(studentId!==''&&option.dataset.studentId!==studentId)||(invoiceSearch?.value&& !option.textContent.toLocaleLowerCase().includes(invoiceSearch.value.toLocaleLowerCase()));
        }
        invoiceSelect.dispatchEvent(new Event('options-updated'));
        const chosen=invoiceSelect.selectedOptions[0];
        if(chosen?.value&&chosen.hidden){invoiceSelect.value='';invoiceSelect.dispatchEvent(new Event('change',{bubbles:true}));if(feeAmount)feeAmount.value='';}
    };
    studentSelect.addEventListener('change',filterStudentInvoices);
    filterStudentInvoices();
    invoiceSelect.addEventListener('change',()=>{
        const option=invoiceSelect.selectedOptions[0];
        if(!option?.value){const balanceLabel=document.getElementById('paymentBalance');if(balanceLabel)balanceLabel.textContent='Select a bill';return;}
        studentSelect.value=option.dataset.studentId||'';
        studentSelect.dispatchEvent(new Event('change',{bubbles:true}));
        if(feeAmount){if(!feeAmount.value||invoiceSelect.value!==initialInvoiceId)feeAmount.value=option.dataset.balance||'';feeAmount.max=option.dataset.balance||'';}
        const balanceLabel=document.getElementById('paymentBalance');
        if(balanceLabel)balanceLabel.textContent='₨ '+Number(option.dataset.balance||0).toLocaleString('en-PK');
    });
    if(invoiceSelect.value)invoiceSelect.dispatchEvent(new Event('change'));
}
if(studentSelect&&document.getElementById('invoice_month')){
    const originalStudentId=studentSelect.value;
    const feePreview=document.getElementById('feeHeadPreview');
    const isExistingBill=feePreview?.dataset.edit==='1';
    const renderFeeBreakdown=student=>{
        if(!feePreview||!student?.dataset.feeHeads)return;
        let items=[];
        try{items=JSON.parse(student.dataset.feeHeads);}catch{return;}
        feePreview.replaceChildren();
        const heading=document.createElement('strong');heading.textContent='This student’s monthly fee heads';feePreview.append(heading);
        for(const item of items){const row=document.createElement('span');const label=document.createTextNode(item.label+' ');const amount=document.createElement('b');amount.textContent='₨ '+Number(item.amount).toLocaleString('en-PK');row.append(label,amount);feePreview.append(row);}
    };
    studentSelect.addEventListener('change',()=>{
        const student=studentSelect.selectedOptions[0];
        const amount=document.getElementById('amount');
        if(amount&&student?.dataset.feeTotal&&(!isExistingBill||studentSelect.value!==originalStudentId))amount.value=student.dataset.feeTotal;
        renderFeeBreakdown(student);
    });
    if(studentSelect.value&&(!isExistingBill||!document.getElementById('amount')?.value))studentSelect.dispatchEvent(new Event('change'));
}
const billAmount=document.getElementById('amount');
if(document.getElementById('invoice_month')&&billAmount){
    const discount=document.getElementById('discount');
    const lateFee=document.getElementById('late_fee');
    const updateBillTotal=()=>{
        const total=Math.max(0,Number(billAmount.value||0)-Number(discount?.value||0)+Number(lateFee?.value||0));
        const display=document.getElementById('feeBillTotal');
        if(display)display.textContent='₨ '+total.toLocaleString('en-PK');
    };
    [billAmount,discount,lateFee].filter(Boolean).forEach(input=>input.addEventListener('input',updateBillTotal));
    updateBillTotal();
}

const bulkFeeHeadPreview=document.getElementById('bulkFeeHeads');
if(bulkFeeHeadPreview&&classSelect){
    const updateBulkFeeHeads=()=>{
        const classId=classSelect.value;
        bulkFeeHeadPreview.querySelectorAll('.bulk-head-item[data-class-id]').forEach(item=>{
            item.hidden=classId!==''&&item.dataset.classId!==classId;
        });
    };
    classSelect.addEventListener('change',updateBulkFeeHeads);
    updateBulkFeeHeads();
}

// Clickable report headings sort the matching rows immediately in the browser.
document.querySelectorAll('.sortable-table th[data-sort]').forEach((heading,index)=>{
    if(heading.classList.contains('server-sort'))return;
    heading.tabIndex=0;
    heading.setAttribute('role','button');
    heading.setAttribute('aria-label','Sort by '+heading.textContent.trim());
    let ascending=true;
    const sortRows=()=>{
        const table=heading.closest('table');
        const body=table?.tBodies[0];
        if(!body)return;
        const rows=Array.from(body.rows).filter(row=>!row.querySelector('.table-empty,.fee-empty'));
        const cellIndex=Array.from(heading.parentElement.children).indexOf(heading);
        const value=row=>row.cells[cellIndex]?.innerText.trim().replace(/\s+/g,' ')||'';
        const number=value=>Number(value.replace(/[^\d.-]/g,''));
        rows.sort((left,right)=>{
            const a=value(left),b=value(right),an=number(a),bn=number(b);
            const numeric=/[\d]/.test(a)&&/[\d]/.test(b)&&!/[a-z]{3,}/i.test(a+b);
            const dateLike=/^\d{1,2}\s+[a-z]{3,}\s+\d{4}$/i.test(a)&&/^\d{1,2}\s+[a-z]{3,}\s+\d{4}$/i.test(b);
            const ad=dateLike?Date.parse(a):NaN,bd=dateLike?Date.parse(b):NaN;
            const comparison=numeric&&Number.isFinite(an)&&Number.isFinite(bn)?an-bn:(dateLike&&Number.isFinite(ad)&&Number.isFinite(bd)?ad-bd:a.localeCompare(b,undefined,{numeric:true,sensitivity:'base'}));
            return ascending?comparison:-comparison;
        });
        rows.forEach(row=>body.append(row));
        table.querySelectorAll('th[data-sort]').forEach(item=>item.removeAttribute('aria-sort'));
        heading.setAttribute('aria-sort',ascending?'ascending':'descending');
        ascending=!ascending;
    };
    heading.addEventListener('click',sortRows);
    heading.addEventListener('keydown',event=>{if(event.key==='Enter'||event.key===' '){event.preventDefault();sortRows();}});
});
