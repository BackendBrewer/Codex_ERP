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
    input.addEventListener('input',()=>{
        const term=input.value.trim().toLocaleLowerCase();
        for(const option of select.options){
            if(!option.value)continue;
            option.hidden=!option.textContent.toLocaleLowerCase().includes(term);
        }
    });
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
        const chosen=invoiceSelect.selectedOptions[0];
        if(chosen?.value&&chosen.hidden){invoiceSelect.value='';if(feeAmount)feeAmount.value='';}
    };
    studentSelect.addEventListener('change',filterStudentInvoices);
    filterStudentInvoices();
    invoiceSelect.addEventListener('change',()=>{
        const option=invoiceSelect.selectedOptions[0];
        if(!option?.value){const balanceLabel=document.getElementById('paymentBalance');if(balanceLabel)balanceLabel.textContent='Select a bill';return;}
        studentSelect.value=option.dataset.studentId||'';
        filterStudentInvoices();
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
