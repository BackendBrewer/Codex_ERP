const menuToggle=document.getElementById('menuToggle');
const sidebar=document.getElementById('sidebar');
const sidebarScrim=document.getElementById('sidebarScrim');
function closeSidebar(){sidebar?.classList.remove('open');sidebarScrim?.classList.remove('open')}
menuToggle?.addEventListener('click',()=>{sidebar?.classList.toggle('open');sidebarScrim?.classList.toggle('open')});
sidebarScrim?.addEventListener('click',closeSidebar);
