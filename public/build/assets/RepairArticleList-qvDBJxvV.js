import{f as Z,o as ee,a as k,c as d,b as t,k as f,j as h,x as L,d as o,v as r,e as y,F as te,i as se,t as i,q as I,w as le,M as F,T as K,r as u,h as n,y as M}from"./app-B026o9N6.js";import{r as ae}from"./PlusIcon-BCrpNFtC.js";import{r as ie}from"./XMarkIcon-DXEdBYP8.js";import{r as ne}from"./PencilIcon-Vwoq4PUQ.js";import{r as O}from"./PrinterIcon-tmFO_x1L.js";import{r as oe}from"./TrashIcon-1RYRyzi4.js";const de={class:"space-y-6"},re={class:"flex items-center justify-between"},pe={class:"card p-4"},ce={class:"card overflow-x-auto"},ue={class:"min-w-full text-sm"},ge={key:0},me={key:1},ve={class:"px-3 py-2 whitespace-nowrap"},xe={class:"px-3 py-2"},be={class:"px-3 py-2 font-medium"},fe=["title"],he={class:"px-3 py-2"},ye={class:"px-3 py-2"},_e={class:"px-3 py-2 text-right"},$e={class:"px-3 py-2 text-right"},ke={class:"px-3 py-2 text-right"},De={class:"px-3 py-2 text-center"},Ce={key:0,class:"text-green-600 font-bold text-xs bg-green-100 px-2 py-0.5 rounded-full"},Ae={key:1,class:"text-gray-400 text-xs"},ze={class:"px-3 py-2 text-center"},Ne={key:0,class:"text-blue-600 font-bold text-xs bg-blue-100 px-2 py-0.5 rounded-full"},Te={key:1,class:"text-gray-400 text-xs"},Re={class:"px-3 py-2 text-right font-semibold"},we={class:"px-3 py-2 text-center"},Le={class:"flex items-center justify-center gap-1"},Ve=["onClick"],je=["onClick"],Se=["onClick"],Ue=["onClick"],Ee={key:0,class:"flex items-center justify-between px-4 py-3 border-t border-gray-100"},Be={class:"text-sm text-gray-500"},Pe={class:"flex gap-2"},We=["disabled"],Ie=["disabled"],Fe={key:0,class:"fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"},Ke={class:"bg-white rounded-xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto"},Me={class:"flex items-center justify-between p-5 border-b"},Oe={class:"text-lg font-bold"},Ge={class:"grid grid-cols-2 gap-4"},Ye={class:"col-span-2"},qe={class:"flex items-center gap-6 col-span-2 pt-1"},He={class:"flex items-center gap-2 cursor-pointer select-none"},Je={class:"flex items-center gap-2 cursor-pointer select-none"},Qe={class:"col-span-2"},Xe={key:0,class:"text-sm text-red-600"},Ze={class:"flex justify-end gap-3 pt-2"},et=["disabled"],tt={key:0,class:"fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"},st={class:"bg-white rounded-xl shadow-2xl w-full max-w-sm p-6 space-y-4"},lt={class:"text-sm text-gray-600"},at={class:"flex justify-end gap-3"},it=["disabled"],gt={__name:"RepairArticleList",setup(nt){function V(s){return s?s.slice(0,10).replace(/-/g,"."):"—"}const A=u({}),z=u([]),N=u(!1),T=u(""),p=u({current_page:1,last_page:1}),R=u(!1),v=u(null),D=u(!1),_=u(""),x=u(null),C=u(!1),j=()=>({bill_number:"",received_date:new Date().toISOString().slice(0,10),give_date:"",article:"",damage:"",customer_name:"",telephone:"",weight:"",add_weight:"",advance:"",price:"",done:!1,given:!1,notes:""}),a=Z(j());let S=null;function G(){clearTimeout(S),S=setTimeout(()=>$(1),300)}async function $(s=1){N.value=!0;try{const{data:e}=await k.get("/api/repair-articles",{params:{search:T.value,page:s}});z.value=e.data,p.value={current_page:e.current_page,last_page:e.last_page}}finally{N.value=!1}}function U(s){s>=1&&s<=p.value.last_page&&$(s)}function Y(s){return!s.give_date||s.given?!1:new Date(s.give_date)<new Date}function E(s=null){var e,l;_.value="",s?(v.value=s.id,Object.assign(a,{bill_number:s.bill_number??"",received_date:((e=s.received_date)==null?void 0:e.slice(0,10))??"",give_date:((l=s.give_date)==null?void 0:l.slice(0,10))??"",article:s.article,damage:s.damage??"",customer_name:s.customer_name??"",telephone:s.telephone??"",weight:s.weight??"",add_weight:s.add_weight??"",advance:s.advance??"",price:s.price??"",done:s.done,given:s.given,notes:s.notes??""})):(v.value=null,Object.assign(a,j())),R.value=!0}function w(){R.value=!1}async function q(){var s,e,l,c,m;D.value=!0,_.value="";try{const g={...a};v.value?await k.put(`/api/repair-articles/${v.value}`,g):await k.post("/api/repair-articles",g),w(),$(p.value.current_page)}catch(g){_.value=((e=(s=g.response)==null?void 0:s.data)==null?void 0:e.message)??((m=Object.values(((c=(l=g.response)==null?void 0:l.data)==null?void 0:c.errors)??{})[0])==null?void 0:m[0])??"Failed to save."}finally{D.value=!1}}function H(s){const e=A.value,l=new Date().toLocaleDateString("en-LK",{day:"2-digit",month:"short",year:"numeric"}),c=W=>W?new Date(W).toLocaleDateString("en-LK",{day:"2-digit",month:"short",year:"numeric"}):"—",m=s.advance||0,g=(s.price||0)-m;B(`<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Repair Invoice — ${s.bill_number??s.id}</title>
  <style>${P()}</style></head><body>
  <div class="hdr">
    <div style="display:flex;align-items:flex-start;gap:10px">
      ${e.logo_url?`<img src="${e.logo_url}" class="logo">`:""}
      <div>
        <div class="shop-name">${e.shop_name||""}</div>
        ${e.address?`<div class="shop-sub" style="white-space:pre-line">${e.address}</div>`:""}
        ${e.phone?`<div class="shop-sub">Tel: ${e.phone}</div>`:""}
        ${e.br_number?`<div class="shop-sub">BR No: ${e.br_number}</div>`:""}
      </div>
    </div>
    <div class="meta-r">
      <div class="inv-title" style="color:#1d4ed8">REPAIR INVOICE</div>
      <div style="font-size:10px;color:#888;margin-bottom:4px">Completion Receipt</div>
      <table class="meta-table">
        ${s.bill_number?`<tr><td>Bill No</td><td><strong>${s.bill_number}</strong></td></tr>`:""}
        <tr><td>Received</td><td>${c(s.received_date)}</td></tr>
        <tr><td>Collected</td><td>${l}</td></tr>
      </table>
    </div>
  </div>
  <div class="cust">
    <strong>Customer:</strong> ${s.customer_name||"Walk-in"}
    ${s.telephone?` &nbsp;|&nbsp; Tel: ${s.telephone}`:""}
  </div>
  <table class="items">
    <thead><tr>
      <th style="text-align:left">Article</th>
      <th style="text-align:left">Damage / Work Done</th>
      <th style="text-align:right;width:70px">Weight (g)</th>
      <th style="text-align:right;width:80px">Add Wt (g)</th>
    </tr></thead>
    <tbody>
      <tr>
        <td style="font-weight:700">${s.article}</td>
        <td>${s.damage||"—"}</td>
        <td style="text-align:right">${s.weight||"—"}</td>
        <td style="text-align:right">${s.add_weight||"—"}</td>
      </tr>
    </tbody>
  </table>
  <div class="totals">
    <div class="totals-box">
      <div class="tline"><span>Total Repair Charge</span><span>${b(s.price)}</span></div>
      <div class="tline"><span>Advance Paid</span><span>(${b(m)})</span></div>
      <div class="grand" style="color:#1d4ed8"><span>Balance Collected</span><span>${b(g)}</span></div>
    </div>
  </div>
  ${s.notes?`<div style="margin-top:10px;font-size:10px;color:#555"><strong>Notes:</strong> ${s.notes}</div>`:""}
  <div class="sigs">
    <div class="sig">Customer Signature</div>
    <div class="sig">Authorised By</div>
  </div>
  <div class="footer">
    <div style="font-weight:600">Thank you for choosing us!</div>
    ${e.shop_name?`<div style="font-size:10px;color:#888;margin-top:2px">${e.shop_name}</div>`:""}
  </div>
  </body></html>`)}function J(s){x.value=s}async function Q(){C.value=!0;try{await k.delete(`/api/repair-articles/${x.value.id}`),x.value=null,$(p.value.current_page)}finally{C.value=!1}}ee(async()=>{$();const{data:s}=await k.get("/api/shop-branding").catch(()=>({data:{}}));A.value=s??{}});function b(s){return"LKR "+Number(s||0).toLocaleString("en-LK",{minimumFractionDigits:2,maximumFractionDigits:2})}function B(s){const e=window.open("","_blank","width=800,height=900");e.document.write(s),e.document.close(),e.addEventListener("load",()=>{e.focus(),e.print()})}function P(){return`
    @media print { @page { size: A5; margin: 8mm 10mm; } }
    * { box-sizing: border-box; }
    body { font-family: Arial, Helvetica, sans-serif; font-size: 8.5px; color: #111; margin: 0; padding: 8px 12px; }
    .hdr { display:flex; justify-content:space-between; align-items:flex-start; gap:10px; margin-bottom:8px; padding-bottom:7px; border-bottom:1.5px solid #1a1a1a; }
    .logo { max-height:40px; max-width:60px; object-fit:contain; }
    .shop-name { font-size:11px; font-weight:800; letter-spacing:0.5px; text-transform:uppercase; margin-bottom:1px; }
    .shop-sub { font-size:7.5px; color:#555; line-height:1.4; }
    .meta-r { text-align:right; min-width:120px; }
    .inv-title { font-size:12px; font-weight:900; letter-spacing:1.5px; margin-bottom:4px; }
    .meta-table { font-size:7.5px; border-collapse:collapse; margin-left:auto; }
    .meta-table td { padding:1px 3px; }
    .meta-table td:first-child { color:#888; text-align:right; }
    .meta-table td:last-child { font-size:8px; text-align:left; }
    .cust { font-size:8.5px; background:#f9f9f9; border:1px solid #e5e7eb; padding:4px 8px; border-radius:3px; margin-bottom:7px; }
    table.items { width:100%; border-collapse:collapse; font-size:8.5px; margin-bottom:7px; }
    table.items thead tr { background:#1a1a1a; color:#fff; }
    table.items th { padding:3px 5px; font-size:7.5px; font-weight:700; letter-spacing:0.3px; }
    table.items tbody tr { border-bottom:1px solid #e5e7eb; }
    table.items td { padding:3px 5px; vertical-align:top; }
    .totals { display:flex; justify-content:flex-end; margin-top:6px; }
    .totals-box { min-width:190px; }
    .tline { display:flex; justify-content:space-between; font-size:8.5px; padding:2px 0; border-bottom:1px dashed #e5e7eb; }
    .grand { display:flex; justify-content:space-between; font-size:11px; font-weight:800; border-top:1.5px solid #1a1a1a; border-bottom:1.5px solid #1a1a1a; padding:3px 0; margin:2px 0; }
    .footer { text-align:center; margin-top:12px; padding-top:7px; border-top:1px dashed #ccc; font-size:8px; }
    .sigs { display:flex; justify-content:space-between; margin-top:24px; }
    .sig { border-top:1px solid #374151; width:130px; text-align:center; padding-top:3px; font-size:7.5px; color:#6b7280; }
  `}function X(s){const e=A.value,l=new Date().toLocaleDateString("en-LK",{day:"2-digit",month:"short",year:"numeric"}),c=g=>g?new Date(g).toLocaleDateString("en-LK",{day:"2-digit",month:"short",year:"numeric"}):"—",m=(s.price||0)-(s.advance||0);B(`<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Advance Invoice — ${s.bill_number??s.id}</title>
  <style>${P()}</style></head><body>
  <div class="hdr">
    <div style="display:flex;align-items:flex-start;gap:10px">
      ${e.logo_url?`<img src="${e.logo_url}" class="logo">`:""}
      <div>
        <div class="shop-name">${e.shop_name||""}</div>
        ${e.address?`<div class="shop-sub" style="white-space:pre-line">${e.address}</div>`:""}
        ${e.phone?`<div class="shop-sub">Tel: ${e.phone}</div>`:""}
        ${e.br_number?`<div class="shop-sub">BR No: ${e.br_number}</div>`:""}
      </div>
    </div>
    <div class="meta-r">
      <div class="inv-title" style="color:#059669">ADVANCE RECEIPT</div>
      <div style="font-size:10px;color:#888;margin-bottom:4px">Repair Article</div>
      <table class="meta-table">
        ${s.bill_number?`<tr><td>Bill No</td><td><strong>${s.bill_number}</strong></td></tr>`:""}
        <tr><td>Received</td><td>${c(s.received_date)}</td></tr>
        ${s.give_date?`<tr><td>Expected</td><td>${c(s.give_date)}</td></tr>`:""}
        <tr><td>Printed</td><td>${l}</td></tr>
      </table>
    </div>
  </div>
  <div class="cust">
    <strong>Customer:</strong> ${s.customer_name||"Walk-in"}
    ${s.telephone?` &nbsp;|&nbsp; Tel: ${s.telephone}`:""}
  </div>
  <table class="items">
    <thead><tr>
      <th style="text-align:left">Article</th>
      <th style="text-align:left">Damage / Work</th>
      <th style="text-align:right;width:70px">Weight (g)</th>
      <th style="text-align:right;width:80px">Add Wt (g)</th>
    </tr></thead>
    <tbody>
      <tr>
        <td style="font-weight:700">${s.article}</td>
        <td>${s.damage||"—"}</td>
        <td style="text-align:right">${s.weight||"—"}</td>
        <td style="text-align:right">${s.add_weight||"—"}</td>
      </tr>
    </tbody>
  </table>
  <div class="totals">
    <div class="totals-box">
      ${s.price?`<div class="tline"><span>Estimated Price</span><span>${b(s.price)}</span></div>`:""}
      <div class="grand" style="color:#059669"><span>Advance Received</span><span>${b(s.advance)}</span></div>
      ${s.price?`<div class="tline" style="color:#dc2626;font-weight:700"><span>Balance Due</span><span>${b(m)}</span></div>`:""}
    </div>
  </div>
  ${s.notes?`<div style="margin-top:10px;font-size:10px;color:#555"><strong>Notes:</strong> ${s.notes}</div>`:""}
  <div class="sigs">
    <div class="sig">Customer Signature</div>
    <div class="sig">Authorised By</div>
  </div>
  <div class="footer">
    <div style="font-weight:600">Thank you! We'll notify you when your item is ready.</div>
    <div style="font-size:10px;color:#888;margin-top:2px">Balance payable on collection of the completed item.</div>
    ${e.shop_name?`<div style="font-size:10px;color:#888;margin-top:2px">${e.shop_name}</div>`:""}
  </div>
  </body></html>`)}return(s,e)=>(n(),d("div",de,[t("div",re,[e[20]||(e[20]=t("div",null,[t("h1",{class:"text-2xl font-bold text-gray-900"},"Repair Article List"),t("p",{class:"text-sm text-gray-500 mt-1"},"Track articles received for repair")],-1)),t("button",{onClick:e[0]||(e[0]=l=>E()),class:"btn-primary flex items-center gap-2"},[f(h(ae),{class:"w-4 h-4"}),e[19]||(e[19]=L(" Add Repair ",-1))])]),t("div",pe,[o(t("input",{"onUpdate:modelValue":e[1]||(e[1]=l=>T.value=l),onInput:G,type:"text",placeholder:"Search by article, customer, bill no, phone…",class:"form-input w-full max-w-md"},null,544),[[r,T.value]])]),t("div",ce,[t("table",ue,[e[23]||(e[23]=t("thead",null,[t("tr",{class:"bg-gray-900 text-white text-xs uppercase"},[t("th",{class:"px-3 py-3 text-left"},"Date"),t("th",{class:"px-3 py-3 text-left"},"Bill No"),t("th",{class:"px-3 py-3 text-left"},"Give Date"),t("th",{class:"px-3 py-3 text-left"},"Article"),t("th",{class:"px-3 py-3 text-left"},"Damage"),t("th",{class:"px-3 py-3 text-left"},"Customer Name"),t("th",{class:"px-3 py-3 text-left"},"Telephone No"),t("th",{class:"px-3 py-3 text-right"},"Weight"),t("th",{class:"px-3 py-3 text-right"},"Add Weight"),t("th",{class:"px-3 py-3 text-right"},"Advance"),t("th",{class:"px-3 py-3 text-center"},"Done"),t("th",{class:"px-3 py-3 text-center"},"Give"),t("th",{class:"px-3 py-3 text-right"},"Price"),t("th",{class:"px-3 py-3 text-center"},"Actions")])],-1)),t("tbody",null,[N.value?(n(),d("tr",ge,[...e[21]||(e[21]=[t("td",{colspan:"14",class:"text-center py-8 text-gray-400"},"Loading…",-1)])])):z.value.length?y("",!0):(n(),d("tr",me,[...e[22]||(e[22]=[t("td",{colspan:"14",class:"text-center py-8 text-gray-400"},"No repair articles found.",-1)])])),(n(!0),d(te,null,se(z.value,l=>(n(),d("tr",{key:l.id,class:M(["border-b border-gray-100 hover:bg-gray-50 transition-colors",{"bg-green-50":l.given}])},[t("td",ve,i(V(l.received_date)),1),t("td",xe,i(l.bill_number??"—"),1),t("td",{class:M(["px-3 py-2 whitespace-nowrap",Y(l)?"text-red-600 font-semibold":""])},i(V(l.give_date)),3),t("td",be,i(l.article),1),t("td",{class:"px-3 py-2 text-gray-600 max-w-[200px] truncate",title:l.damage},i(l.damage??"—"),9,fe),t("td",he,i(l.customer_name??"—"),1),t("td",ye,i(l.telephone??"—"),1),t("td",_e,i(l.weight??"—"),1),t("td",$e,i(l.add_weight??"—"),1),t("td",ke,i(l.advance?Number(l.advance).toLocaleString():"—"),1),t("td",De,[l.done?(n(),d("span",Ce,"YES")):(n(),d("span",Ae,"—"))]),t("td",ze,[l.given?(n(),d("span",Ne,"YES")):(n(),d("span",Te,"—"))]),t("td",Re,i(Number(l.price).toLocaleString()),1),t("td",we,[t("div",Le,[t("button",{onClick:c=>E(l),class:"p-1.5 text-blue-600 hover:bg-blue-50 rounded",title:"Edit"},[f(h(ne),{class:"w-4 h-4"})],8,Ve),t("button",{onClick:c=>X(l),class:"p-1.5 text-emerald-600 hover:bg-emerald-50 rounded",title:"Print Advance Invoice"},[f(h(O),{class:"w-4 h-4"})],8,je),l.given?(n(),d("button",{key:0,onClick:c=>H(l),class:"p-1.5 text-blue-600 hover:bg-blue-50 rounded",title:"Print Completion Invoice"},[f(h(O),{class:"w-4 h-4"})],8,Se)):y("",!0),t("button",{onClick:c=>J(l),class:"p-1.5 text-red-500 hover:bg-red-50 rounded",title:"Delete"},[f(h(oe),{class:"w-4 h-4"})],8,Ue)])])],2))),128))])]),p.value.last_page>1?(n(),d("div",Ee,[t("p",Be,"Page "+i(p.value.current_page)+" of "+i(p.value.last_page),1),t("div",Pe,[t("button",{onClick:e[2]||(e[2]=l=>U(p.value.current_page-1)),disabled:p.value.current_page===1,class:"px-3 py-1 text-sm border rounded disabled:opacity-40"},"Prev",8,We),t("button",{onClick:e[3]||(e[3]=l=>U(p.value.current_page+1)),disabled:p.value.current_page===p.value.last_page,class:"px-3 py-1 text-sm border rounded disabled:opacity-40"},"Next",8,Ie)])])):y("",!0)]),(n(),I(K,{to:"body"},[R.value?(n(),d("div",Fe,[t("div",Ke,[t("div",Me,[t("h2",Oe,i(v.value?"Edit Repair Article":"Add Repair Article"),1),t("button",{onClick:w,class:"text-gray-400 hover:text-gray-600"},[f(h(ie),{class:"w-5 h-5"})])]),t("form",{onSubmit:le(q,["prevent"]),class:"p-5 space-y-4"},[t("div",Ge,[t("div",null,[e[24]||(e[24]=t("label",{class:"form-label"},"Received Date *",-1)),o(t("input",{"onUpdate:modelValue":e[4]||(e[4]=l=>a.received_date=l),type:"date",required:"",class:"form-input"},null,512),[[r,a.received_date]])]),t("div",null,[e[25]||(e[25]=t("label",{class:"form-label"},"Bill No",-1)),o(t("input",{"onUpdate:modelValue":e[5]||(e[5]=l=>a.bill_number=l),type:"text",class:"form-input",placeholder:"e.g. 1409"},null,512),[[r,a.bill_number]])]),t("div",null,[e[26]||(e[26]=t("label",{class:"form-label"},"Give Date",-1)),o(t("input",{"onUpdate:modelValue":e[6]||(e[6]=l=>a.give_date=l),type:"date",class:"form-input"},null,512),[[r,a.give_date]])]),t("div",null,[e[27]||(e[27]=t("label",{class:"form-label"},"Article *",-1)),o(t("input",{"onUpdate:modelValue":e[7]||(e[7]=l=>a.article=l),type:"text",required:"",class:"form-input",placeholder:"e.g. Chain, Ring…"},null,512),[[r,a.article]])]),t("div",Ye,[e[28]||(e[28]=t("label",{class:"form-label"},"Damage / Work Description",-1)),o(t("textarea",{"onUpdate:modelValue":e[8]||(e[8]=l=>a.damage=l),class:"form-input",rows:"2",placeholder:"Describe the damage or work needed…"},null,512),[[r,a.damage]])]),t("div",null,[e[29]||(e[29]=t("label",{class:"form-label"},"Customer Name",-1)),o(t("input",{"onUpdate:modelValue":e[9]||(e[9]=l=>a.customer_name=l),type:"text",class:"form-input"},null,512),[[r,a.customer_name]])]),t("div",null,[e[30]||(e[30]=t("label",{class:"form-label"},"Telephone No",-1)),o(t("input",{"onUpdate:modelValue":e[10]||(e[10]=l=>a.telephone=l),type:"text",class:"form-input"},null,512),[[r,a.telephone]])]),t("div",null,[e[31]||(e[31]=t("label",{class:"form-label"},"Weight (g)",-1)),o(t("input",{"onUpdate:modelValue":e[11]||(e[11]=l=>a.weight=l),type:"number",step:"0.001",min:"0",class:"form-input"},null,512),[[r,a.weight]])]),t("div",null,[e[32]||(e[32]=t("label",{class:"form-label"},"Add Weight (g)",-1)),o(t("input",{"onUpdate:modelValue":e[12]||(e[12]=l=>a.add_weight=l),type:"number",step:"0.001",min:"0",class:"form-input"},null,512),[[r,a.add_weight]])]),t("div",null,[e[33]||(e[33]=t("label",{class:"form-label"},"Advance (LKR)",-1)),o(t("input",{"onUpdate:modelValue":e[13]||(e[13]=l=>a.advance=l),type:"number",step:"0.01",min:"0",class:"form-input"},null,512),[[r,a.advance]])]),t("div",null,[e[34]||(e[34]=t("label",{class:"form-label"},"Price (LKR)",-1)),o(t("input",{"onUpdate:modelValue":e[14]||(e[14]=l=>a.price=l),type:"number",step:"0.01",min:"0",class:"form-input"},null,512),[[r,a.price]])]),t("div",qe,[t("label",He,[o(t("input",{"onUpdate:modelValue":e[15]||(e[15]=l=>a.done=l),type:"checkbox",class:"w-4 h-4 accent-green-600"},null,512),[[F,a.done]]),e[35]||(e[35]=t("span",{class:"text-sm font-medium"},"Done",-1))]),t("label",Je,[o(t("input",{"onUpdate:modelValue":e[16]||(e[16]=l=>a.given=l),type:"checkbox",class:"w-4 h-4 accent-blue-600"},null,512),[[F,a.given]]),e[36]||(e[36]=t("span",{class:"text-sm font-medium"},"Given back to customer",-1))])]),t("div",Qe,[e[37]||(e[37]=t("label",{class:"form-label"},"Notes",-1)),o(t("textarea",{"onUpdate:modelValue":e[17]||(e[17]=l=>a.notes=l),class:"form-input",rows:"2"},null,512),[[r,a.notes]])])]),_.value?(n(),d("p",Xe,i(_.value),1)):y("",!0),t("div",Ze,[t("button",{type:"button",onClick:w,class:"btn-secondary"},"Cancel"),t("button",{type:"submit",disabled:D.value,class:"btn-primary"},i(D.value?"Saving…":v.value?"Update":"Add"),9,et)])],32)])])):y("",!0)])),(n(),I(K,{to:"body"},[x.value?(n(),d("div",tt,[t("div",st,[e[40]||(e[40]=t("h3",{class:"text-lg font-bold text-gray-900"},"Delete Repair Article?",-1)),t("p",lt,[e[38]||(e[38]=L(" Are you sure you want to delete ",-1)),t("strong",null,i(x.value.article),1),e[39]||(e[39]=L("? This cannot be undone. ",-1))]),t("div",at,[t("button",{onClick:e[18]||(e[18]=l=>x.value=null),class:"btn-secondary"},"Cancel"),t("button",{onClick:Q,disabled:C.value,class:"btn-danger"},i(C.value?"Deleting…":"Delete"),9,it)])])])):y("",!0)]))]))}};export{gt as default};
