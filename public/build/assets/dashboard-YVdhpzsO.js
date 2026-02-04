class a{constructor(){this.init()}init(){this.setupSidebarToggle(),this.setupNotifications(),this.setupResponsiveLayout(),this.setupFormValidation(),console.log("DDU Dashboard inicializado correctamente")}setupSidebarToggle(){const e=document.getElementById("sidebar-toggle"),t=document.querySelector("nav"),s=document.getElementById("sidebar-overlay");e&&t&&e.addEventListener("click",()=>{t.classList.toggle("open"),s&&s.classList.toggle("hidden")}),s&&s.addEventListener("click",()=>{t.classList.remove("open"),s.classList.add("hidden")})}setupNotifications(){document.querySelectorAll(".notification").forEach(t=>{setTimeout(()=>{this.hideNotification(t)},5e3)}),document.addEventListener("click",t=>{if(t.target.matches(".notification-close")){const s=t.target.closest(".notification");s&&this.hideNotification(s)}})}hideNotification(e){e.style.opacity="0",e.style.transform="translateX(100%)",setTimeout(()=>{e.remove()},300)}setupResponsiveLayout(){const e=()=>{const t=window.innerWidth<768,s=document.querySelector("nav");!t&&s&&s.classList.remove("open")};window.addEventListener("resize",e),e()}setupFormValidation(){document.querySelectorAll("form[data-validate]").forEach(t=>{t.addEventListener("submit",i=>{this.validateForm(t)||i.preventDefault()}),t.querySelectorAll("input, select, textarea").forEach(i=>{i.addEventListener("blur",()=>{this.validateField(i)})})})}validateForm(e){let t=!0;return e.querySelectorAll("input[required], select[required], textarea[required]").forEach(i=>{this.validateField(i)||(t=!1)}),t}validateField(e){const t=e.value.trim(),s=e.hasAttribute("required");let i=!0,r="";return this.clearFieldError(e),s&&!t&&(i=!1,r="Este campo es requerido"),e.type==="email"&&t&&(/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(t)||(i=!1,r="Ingrese un email válido")),i||this.showFieldError(e,r),i}showFieldError(e,t){e.classList.add("border-red-500","bg-red-50"),e.classList.remove("border-gray-300");const s=document.createElement("div");s.className="text-red-600 text-sm mt-1 field-error",s.textContent=t,e.parentNode.appendChild(s)}clearFieldError(e){e.classList.remove("border-red-500","bg-red-50"),e.classList.add("border-gray-300");const t=e.parentNode.querySelector(".field-error");t&&t.remove()}showLoading(e,t="Cargando..."){e&&(e.innerHTML=`
                <div class="flex items-center justify-center space-x-2">
                    <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-ddu-lavanda"></div>
                    <span>${t}</span>
                </div>
            `,e.disabled=!0)}hideLoading(e,t){e&&(e.innerHTML=t,e.disabled=!1)}showNotification(e,t="info",s=5e3){const i=document.createElement("div");i.className="notification fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg max-w-sm transform translate-x-0 opacity-100 transition-all duration-300";const r={success:"bg-green-500",error:"bg-red-500",warning:"bg-yellow-500",info:"bg-blue-500"}[t]||"bg-blue-500";i.className+=` ${r} text-white`,i.innerHTML=`
            <div class="flex items-center justify-between">
                <span class="text-sm font-medium">${e}</span>
                <button class="notification-close ml-2 text-white hover:text-gray-200">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        `,document.body.appendChild(i),s>0&&setTimeout(()=>{this.hideNotification(i)},s)}}class n{constructor(e,t){this.searchInput=document.getElementById(e),this.resultsContainer=document.getElementById(t),this.debounceTimer=null,this.searchInput&&this.resultsContainer&&this.init()}init(){this.searchInput.addEventListener("input",e=>{clearTimeout(this.debounceTimer),this.debounceTimer=setTimeout(()=>{this.searchUsers(e.target.value)},300)})}async searchUsers(e){if(e.length<2){this.resultsContainer.innerHTML="";return}try{this.showSearchLoading();const s=await(await fetch(`/admin/members/search?q=${encodeURIComponent(e)}`,{headers:{"X-Requested-With":"XMLHttpRequest","X-CSRF-TOKEN":document.querySelector('meta[name="csrf-token"]').getAttribute("content")}})).json();this.displayResults(s.users||[])}catch(t){console.error("Error buscando usuarios:",t),this.resultsContainer.innerHTML='<p class="text-red-600 text-sm">Error al buscar usuarios</p>'}}showSearchLoading(){this.resultsContainer.innerHTML=`
            <div class="flex items-center justify-center py-4">
                <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-ddu-lavanda"></div>
                <span class="ml-2 text-sm text-gray-600">Buscando...</span>
            </div>
        `}displayResults(e){if(e.length===0){this.resultsContainer.innerHTML='<p class="text-gray-500 text-sm py-4">No se encontraron usuarios</p>';return}const t=e.map(s=>`
            <div class="user-result p-3 border rounded-lg hover:bg-gray-50 cursor-pointer" data-user-id="${s.id}">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="font-medium text-gray-900">${s.name||"Sin nombre"}</p>
                        <p class="text-sm text-gray-500">${s.email}</p>
                        ${s.is_member?'<span class="text-xs text-green-600">Ya es miembro</span>':""}
                    </div>
                    ${s.is_member?"":`
                        <button class="btn-ddu text-xs px-3 py-1 add-member-btn" data-user-id="${s.id}">
                            Agregar
                        </button>
                    `}
                </div>
            </div>
        `).join("");this.resultsContainer.innerHTML=t,this.resultsContainer.addEventListener("click",this.handleResultClick.bind(this))}handleResultClick(e){if(e.target.matches(".add-member-btn")){const t=e.target.dataset.userId;this.addMember(t)}}async addMember(e){console.log("Agregar miembro:",e)}}document.addEventListener("DOMContentLoaded",()=>{window.dashboardManager=new a,document.getElementById("user-search")&&(window.userSearchManager=new n("user-search","search-results"))});window.DashboardManager=a;window.UserSearchManager=n;
