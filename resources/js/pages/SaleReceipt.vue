<template>
  <div :data-print-mode="printMode">

    <!-- â"€â"€ Screen toolbar (no-print) â"€â"€ -->
    <div class="no-print flex flex-wrap items-center justify-between gap-3 mb-6">
      <div class="flex items-center gap-3">
        <router-link to="/sales"
          class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-800 transition-colors">
          <ArrowLeftIcon class="w-4 h-4" /> Back to Sales
        </router-link>
        <span class="text-gray-300">/</span>
        <span class="text-sm font-medium text-gray-700">{{ sale?.invoice_number }}</span>
      </div>

      <div class="flex flex-wrap gap-2 items-center">
        <!-- Print mode toggle -->
        <div class="flex border border-gray-200 rounded-lg overflow-hidden text-sm">
          <button @click="printMode = 'pos'"
            :class="printMode === 'pos' ? 'bg-amber-500 text-white' : 'bg-white text-gray-600 hover:bg-gray-50'"
            class="px-3 py-1.5 flex items-center gap-1.5 font-medium transition-colors">
            <PrinterIcon class="w-4 h-4" /> POS
          </button>
          <button @click="printMode = 'a5'"
            :class="printMode === 'a5' ? 'bg-amber-500 text-white' : 'bg-white text-gray-600 hover:bg-gray-50'"
            class="px-3 py-1.5 flex items-center gap-1.5 font-medium transition-colors border-l border-gray-200">
            <DocumentTextIcon class="w-4 h-4" /> A5
          </button>
        </div>

        <!-- Direct Print (QZ Tray, POS only) -->
        <button v-if="printMode === 'pos'" @click="directPrint"
          :disabled="directPrinting || loading || !sale"
          class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 disabled:opacity-60 text-white rounded-lg font-medium text-sm shadow-sm transition-colors">
          <ArrowPathIcon v-if="directPrinting" class="w-4 h-4 animate-spin" />
          <PrinterIcon v-else class="w-4 h-4" />
          {{ directPrinting ? 'Printing...' : 'Direct Print' }}
        </button>

        <!-- Standard Print -->
        <button @click="printReceipt" :disabled="loading || !sale"
          class="inline-flex items-center gap-2 px-4 py-2 bg-amber-500 hover:bg-amber-600 disabled:opacity-60 text-white rounded-lg font-medium text-sm shadow-sm transition-colors">
          <PrinterIcon class="w-4 h-4" />
          {{ printMode === 'a5' ? 'Print A5 Invoice' : 'Print Receipt' }}
        </button>
      </div>
    </div>

    <div v-if="directPrintError" class="no-print mb-4 px-3 py-2 rounded-lg border border-red-200 bg-red-50 text-red-700 text-sm">
      {{ directPrintError }}
    </div>

    <!-- Loading -->
    <div v-if="loading" class="flex items-center justify-center py-20 text-gray-400">
      <ArrowPathIcon class="w-5 h-5 animate-spin mr-2" /> Loadingâ€¦
    </div>

    <template v-else-if="sale">

      <!-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
           POS / 76mm Thermal Receipt
      â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• -->
      <div v-show="printMode === 'pos'" id="receipt-wrapper">
        <div id="receipt" class="receipt-paper">

          <!-- HEADER -->
          <div style="text-align:center; margin-bottom:6px;">
            <img v-if="shop.logo_url" :src="shop.logo_url" alt="logo"
              style="max-height:40px; max-width:160px; object-fit:contain; display:block; margin:0 auto 4px;" />
            <div style="font-size:15px; font-weight:bold; letter-spacing:1px; text-transform:uppercase;">
              {{ shop.shop_name || appName }}
            </div>
            <div v-if="shop.address" style="font-size:9px; color:#555; white-space:pre-line; margin-top:2px;">{{ shop.address }}</div>
            <div v-if="shop.phone"   style="font-size:9px; color:#555;">Tel: {{ shop.phone }}</div>
            <div v-if="shop.br_number" style="font-size:9px; color:#555;">BR: {{ shop.br_number }}</div>
            <div style="font-size:10px; margin-top:3px;">Sales Receipt</div>
          </div>

          <hr class="receipt-divider-double" />

          <!-- INVOICE META -->
          <div style="font-size:10px; margin-bottom:4px;">
            <div class="flex-row"><span>Invoice :</span><span style="font-weight:bold; float:right;">{{ sale.invoice_number }}</span></div>
            <div class="flex-row"><span>Date    :</span><span style="float:right;">{{ fmtDate(sale.sold_at) }}</span></div>
            <div class="flex-row"><span>Time    :</span><span style="float:right;">{{ formatTime(sale.sold_at) }}</span></div>
            <div class="flex-row"><span>Cashier :</span><span style="float:right;">{{ sale.user?.name ?? 'â€"' }}</span></div>
            <div class="flex-row"><span>Payment :</span><span style="float:right; text-transform:capitalize;">{{ sale.payment_method?.replace('_',' ') }}</span></div>
            <div class="flex-row">
              <span>Status  :</span>
              <span style="float:right; font-weight:bold; text-transform:capitalize;">{{ sale.payment_status?.toUpperCase() }}</span>
            </div>
          </div>

          <hr class="receipt-divider" />

          <!-- CUSTOMER -->
          <div style="font-size:10px; margin-bottom:4px;">
            <div><strong>Customer:</strong> {{ sale.customer?.name ?? 'Walk-in' }}</div>
            <div v-if="sale.customer?.phone">Phone: {{ sale.customer.phone }}</div>
          </div>

          <hr class="receipt-divider" />

          <!-- ITEMS -->
          <div style="font-size:10px;">
            <div style="display:flex; font-weight:bold; border-bottom:1px solid #333; padding-bottom:3px; margin-bottom:3px;">
              <span style="flex:1;">Item</span>
              <span style="width:28px; text-align:center;">Qty</span>
              <span style="width:54px; text-align:right;">Price</span>
              <span style="width:58px; text-align:right;">Total</span>
            </div>
            <div v-for="item in sale.items" :key="item.id" style="margin-bottom:5px;">
              <div style="display:flex; align-items:baseline;">
                <span style="flex:1; font-weight:bold; word-break:break-word; padding-right:4px;">{{ item.product?.name ?? 'Unknown' }}</span>
                <span style="width:28px; text-align:center;">{{ item.quantity }}</span>
                <span style="width:54px; text-align:right;">{{ lkr(item.unit_price) }}</span>
                <span style="width:58px; text-align:right; font-weight:bold;">{{ lkr(item.total) }}</span>
              </div>
              <div style="color:#555; font-size:9px; padding-left:2px; line-height:1.3;">
                <span v-if="item.product?.sku">SKU:{{ item.product.sku }}  </span>
                <span v-if="item.product?.karat">{{ item.product.karat }}</span>
                <span v-if="item.product?.weight"> {{ item.product.weight }}g</span>
              </div>
              <div v-if="item.gold_value || item.making_charge || item.wastage_amount || item.gemstone_value"
                style="font-size:9px; color:#555; padding-left:2px;">
                <span v-if="item.gold_value">Gold:{{ lkr(item.gold_value) }}  </span>
                <span v-if="item.gemstone_value">Gem:{{ lkr(item.gemstone_value) }}  </span>
                <span v-if="item.making_charge">MC:{{ lkr(item.making_charge) }}  </span>
                <span v-if="item.wastage_amount">Wst:{{ lkr(item.wastage_amount) }}</span>
              </div>
              <div v-if="Number(item.discount) > 0" style="font-size:9px; color:#555; padding-left:2px;">
                Item Disc: -{{ lkr(item.discount) }}
              </div>
            </div>
          </div>

          <hr class="receipt-divider-solid" />

          <!-- TOTALS -->
          <div style="font-size:11px;">
            <div v-if="Number(sale.subtotal) !== Number(sale.total)" style="display:flex; justify-content:space-between; margin-bottom:2px;">
              <span>Subtotal</span><span>LKR {{ lkr(sale.subtotal) }}</span>
            </div>
            <div v-if="Number(sale.discount) > 0" style="display:flex; justify-content:space-between; margin-bottom:2px;">
              <span>Discount</span><span>-LKR {{ lkr(sale.discount) }}</span>
            </div>
            <div v-if="Number(sale.tax) > 0" style="display:flex; justify-content:space-between; margin-bottom:2px;">
              <span>Tax ({{ sale.tax_rate }}%)</span><span>+LKR {{ lkr(sale.tax) }}</span>
            </div>
          </div>

          <hr class="receipt-divider-double" />

          <div style="display:flex; justify-content:space-between; font-size:14px; font-weight:bold; margin:4px 0;">
            <span>TOTAL</span><span>LKR {{ lkr(sale.total) }}</span>
          </div>

          <hr class="receipt-divider" />

          <div style="font-size:11px;">
            <div style="display:flex; justify-content:space-between; margin-bottom:2px;">
              <span>Paid</span><span>LKR {{ lkr(sale.amount_paid) }}</span>
            </div>
            <div v-if="Number(sale.amount_paid) > Number(sale.total)" style="display:flex; justify-content:space-between; font-weight:bold;">
              <span>Change</span><span>LKR {{ lkr(Number(sale.amount_paid) - Number(sale.total)) }}</span>
            </div>
            <div v-if="Number(sale.amount_paid) < Number(sale.total)" style="display:flex; justify-content:space-between; font-weight:bold;">
              <span>Balance Due</span><span>LKR {{ lkr(Number(sale.total) - Number(sale.amount_paid)) }}</span>
            </div>
          </div>

          <!-- NOTES -->
          <div v-if="sale.notes" style="margin-top:6px; font-size:9px; color:#555;">
            <hr class="receipt-divider" />
            Note: {{ sale.notes }}
          </div>

          <hr class="receipt-divider" />

          <!-- BARCODE -->
          <div style="text-align:center; margin:6px 0 2px;">
            <canvas ref="barcodeCanvas" style="max-width:100%; height:40px;"></canvas>
            <div style="font-size:9px; letter-spacing:2px; margin-top:2px;">{{ sale.invoice_number }}</div>
          </div>

          <hr class="receipt-divider" />

          <!-- FOOTER -->
          <div style="text-align:center; font-size:10px; line-height:1.6;">
            <div style="font-weight:bold;">*** Thank You! Come Again ***</div>
            <div style="font-size:9px; color:#555;">{{ fmtDate(sale.sold_at) }}</div>
          </div>

        </div>
      </div>

      <!-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
           A5 Invoice
      â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• -->
      <div v-show="printMode === 'a5'" id="invoice-wrapper">
        <div id="invoice" class="a5-paper">

          <!-- SHOP HEADER -->
          <div class="inv-header">
            <div class="inv-logo-block">
              <img v-if="shop.logo_url" :src="shop.logo_url" alt="logo" class="inv-logo" />
              <div>
                <div class="inv-shop-name">{{ shop.shop_name || appName }}</div>
                <div v-if="shop.address" class="inv-shop-sub" style="white-space:pre-line;">{{ shop.address }}</div>
                <div v-if="shop.phone"     class="inv-shop-sub">Tel: {{ shop.phone }}</div>
                <div v-if="shop.br_number" class="inv-shop-sub">BR No: {{ shop.br_number }}</div>
              </div>
            </div>
            <div class="inv-meta-block">
              <table class="inv-meta-table">
                <tr><td>Invoice No</td><td><strong>{{ sale.invoice_number }}</strong></td></tr>
                <tr><td>Date &amp; Time</td><td>{{ fmtDate(sale.sold_at) }}, {{ formatTime(sale.sold_at) }}</td></tr>
                <tr><td>Cashier</td><td>{{ sale.user?.name ?? 'â€"' }}</td></tr>
              </table>
            </div>
          </div>

          <!-- CUSTOMER -->
          <div class="inv-customer">
            <strong>Bill To:</strong>
            {{ sale.customer?.name ?? 'Walk-in Customer' }}
            <span v-if="sale.customer?.phone"> &nbsp;|&nbsp; Tel: {{ sale.customer.phone }}</span>
            <span style="text-transform:capitalize;"> &nbsp;|&nbsp; Payment: {{ sale.payment_method?.replace('_', ' ') }}</span>
            <span> &nbsp;|&nbsp; Status: <strong style="text-transform:uppercase;">{{ sale.payment_status }}</strong></span>
          </div>

          <!-- ITEMS TABLE -->
          <table class="inv-items-table">
            <thead>
              <tr>
                <th style="text-align:left;">Item / Description</th>
                <th style="text-align:center; width:40px;">Qty</th>
                <th style="text-align:right; width:90px;">Unit Price</th>
                <th style="text-align:right; width:70px;">Disc.</th>
                <th style="text-align:right; width:100px;">Total</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="item in sale.items" :key="item.id">
                <td>
                  <div style="font-weight:600;">{{ item.product?.name ?? 'Unknown' }}</div>
                  <div style="font-size:10px; color:#666;">
                    <span v-if="item.product?.sku">SKU: {{ item.product.sku }}</span>
                    <span v-if="item.product?.karat">  Â·  {{ item.product.karat }}</span>
                    <span v-if="item.product?.weight">  Â·  {{ item.product.weight }}g</span>
                  </div>
                  <div v-if="item.gold_value || item.making_charge || item.wastage_amount || item.gemstone_value"
                    style="font-size:10px; color:#888; margin-top:2px;">
                    <span v-if="item.gold_value">Gold: LKR {{ lkr(item.gold_value) }}  </span>
                    <span v-if="item.gemstone_value">Gem: LKR {{ lkr(item.gemstone_value) }}  </span>
                    <span v-if="item.making_charge">Making: LKR {{ lkr(item.making_charge) }}  </span>
                    <span v-if="item.wastage_amount">Wastage: LKR {{ lkr(item.wastage_amount) }}</span>
                  </div>
                </td>
                <td style="text-align:center;">{{ item.quantity }}</td>
                <td style="text-align:right;">LKR {{ lkr(item.unit_price) }}</td>
                <td style="text-align:right;">{{ Number(item.discount) > 0 ? 'LKR ' + lkr(item.discount) : 'â€"' }}</td>
                <td style="text-align:right; font-weight:700;">LKR {{ lkr(item.total) }}</td>
              </tr>
            </tbody>
          </table>

          <!-- TOTALS + PAYMENT SUMMARY -->
          <div class="inv-totals-row">
            <div class="inv-notes">
              <div v-if="sale.notes" style="font-size:11px; color:#555;">
                <strong>Notes:</strong> {{ sale.notes }}
              </div>
            </div>
            <div class="inv-totals">
              <div v-if="Number(sale.subtotal) !== Number(sale.total)" class="inv-total-line">
                <span>Subtotal</span><span>LKR {{ lkr(sale.subtotal) }}</span>
              </div>
              <div v-if="Number(sale.discount) > 0" class="inv-total-line">
                <span>Discount</span><span>- LKR {{ lkr(sale.discount) }}</span>
              </div>
              <div v-if="Number(sale.tax) > 0" class="inv-total-line">
                <span>Tax ({{ sale.tax_rate }}%)</span><span>+ LKR {{ lkr(sale.tax) }}</span>
              </div>
              <div class="inv-total-line inv-grand-total">
                <span>TOTAL</span><span>LKR {{ lkr(sale.total) }}</span>
              </div>
              <div class="inv-total-line">
                <span>Amount Paid</span><span>LKR {{ lkr(sale.amount_paid) }}</span>
              </div>
              <div v-if="Number(sale.amount_paid) > Number(sale.total)" class="inv-total-line" style="color:#16a34a; font-weight:700;">
                <span>Change</span><span>LKR {{ lkr(Number(sale.amount_paid) - Number(sale.total)) }}</span>
              </div>
              <div v-if="Number(sale.amount_paid) < Number(sale.total)" class="inv-total-line" style="color:#dc2626; font-weight:700;">
                <span>Balance Due</span><span>LKR {{ lkr(Number(sale.total) - Number(sale.amount_paid)) }}</span>
              </div>
            </div>
          </div>

          <!-- FOOTER -->
          <div class="inv-footer">
            <div style="font-weight:600;">Thank you for your purchase!</div>
            <div v-if="shop.shop_name" style="font-size:10px; color:#888; margin-top:2px;">{{ shop.shop_name }}</div>
          </div>

        </div>
      </div>

    </template>

    <!-- Error -->
    <div v-if="!loading && !sale" class="text-center py-20 text-gray-400">
      <p>Sale not found.</p>
      <router-link to="/sales" class="text-amber-600 hover:underline text-sm mt-2 inline-block">â† Back to Sales</router-link>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, watch, nextTick } from 'vue'
import { useRoute } from 'vue-router'
import axios from 'axios'
import { ArrowLeftIcon, PrinterIcon, ArrowPathIcon, DocumentTextIcon } from '@heroicons/vue/24/outline'
import { fmtDate } from '../utils/date.js'

const route           = useRoute()
const sale            = ref(null)
const loading         = ref(true)
const barcodeCanvas = ref(null)
const appName         = import.meta.env.VITE_APP_NAME ?? 'Jewellery Store'
const preferredPrinter  = import.meta.env.VITE_THERMAL_PRINTER ?? ''
const directPrinting  = ref(false)
const directPrintError = ref('')

const shop = ref({ shop_name: '', address: '', phone: '', br_number: '', logo_url: '', print_mode: 'a5' })
const printMode = ref('a5')

function lkr(val) {
  return Number(val || 0).toLocaleString('en-LK', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}
function formatTime(d) {
  return new Date(d).toLocaleTimeString('en-LK', { hour: '2-digit', minute: '2-digit' })
}

function drawBarcode(canvas, text) {
  if (!canvas || !text) return
  const W = 255, H = 40
  canvas.width = W; canvas.height = H
  const ctx = canvas.getContext('2d')
  ctx.fillStyle = '#fff'; ctx.fillRect(0, 0, W, H)
  const narrow = 2, wide = 5, gap = 2
  let x = 4; ctx.fillStyle = '#000'
  const C39 = {
    '0':'000110100','1':'100100001','2':'001100001','3':'101100000',
    '4':'000110001','5':'100110000','6':'001110000','7':'000100101',
    '8':'100100100','9':'001100100','A':'100001001','B':'001001001',
    'C':'101001000','D':'000011001','E':'100011000','F':'001011000',
    'G':'000001101','H':'100001100','I':'001001100','J':'000011100',
    'K':'100000011','L':'001000011','M':'101000010','N':'000010011',
    'O':'100010010','P':'001010010','Q':'000000111','R':'100000110',
    'S':'001000110','T':'000010110','U':'110000001','V':'011000001',
    'W':'111000000','X':'010010001','Y':'110010000','Z':'011010000',
    '-':'010000101','.':'110000100',' ':'011000100','*':'010010100',
    '$':'010101000','/':'010100010','+':'010001010','%':'101010000',
  }
  for (const ch of ('*' + text + '*').toUpperCase().split('')) {
    const pattern = C39[ch]; if (!pattern) continue
    for (let i = 0; i < 9; i++) {
      const w = pattern[i] === '1' ? wide : narrow
      if (i % 2 === 0) { ctx.fillStyle = '#000'; ctx.fillRect(x, 0, w, H) }
      x += w
    }
    x += gap
  }
}

function drawAllBarcodes() {
  if (sale.value) {
    drawBarcode(barcodeCanvas.value, sale.value.invoice_number)
  }
}

function printReceipt() {
  document.querySelector('#dyn-page-style')?.remove()
  const s = document.createElement('style')
  s.id = 'dyn-page-style'
  s.textContent = printMode.value === 'a5'
    ? `@media print { @page { size: A5; margin: 12mm 15mm; } }`
    : `@media print { @page { size: 76mm auto; margin: 0; } }`
  document.head.appendChild(s)
  window.print()
}

function buildDirectPrintHtml() {
  const receipt = document.getElementById('receipt')
  if (!receipt) return ''
  return `<!doctype html><html><head><meta charset="utf-8">
<style>
  html,body{margin:0;padding:0;background:#fff;}
  .receipt-paper{width:76mm;max-width:76mm;margin:0;padding:4mm 3mm;box-sizing:border-box;
    font-family:'Courier New',Courier,monospace;font-size:11pt;line-height:1.45;color:#000;background:#fff;}
  .receipt-divider{border:none;border-top:1px dashed #aaa;margin:6px 0;}
  .receipt-divider-solid{border:none;border-top:1px solid #555;margin:6px 0;}
  .receipt-divider-double{border:none;border-top:3px double #333;margin:6px 0;}
  canvas{max-width:100%;}
</style></head><body>${receipt.outerHTML}</body></html>`
}

function qzScriptLoaded() { return typeof window !== 'undefined' && !!window.qz }

function loadQzScript() {
  return new Promise((resolve, reject) => {
    if (qzScriptLoaded()) return resolve()
    const existing = document.querySelector('script[data-qz-tray="1"]')
    if (existing) {
      existing.addEventListener('load', resolve, { once: true })
      existing.addEventListener('error', () => reject(new Error('Failed to load QZ Tray library.')), { once: true })
      return
    }
    const script = document.createElement('script')
    script.src = 'https://cdn.jsdelivr.net/npm/qz-tray@2.2.4/qz-tray.js'
    script.async = true; script.dataset.qzTray = '1'
    script.onload = resolve
    script.onerror = () => reject(new Error('Failed to load QZ Tray library.'))
    document.head.appendChild(script)
  })
}

async function ensureQzConnected() {
  await loadQzScript()
  const qz = window.qz
  if (!qz) throw new Error('QZ Tray library is not available.')
  qz.api.setPromiseType(Promise)
  qz.security.setCertificatePromise(() => Promise.resolve(null))
  qz.security.setSignaturePromise(() => Promise.resolve(''))
  if (!qz.websocket.isActive()) await qz.websocket.connect({ retries: 2, delay: 0.5 })
  return qz
}

async function resolvePrinter(qz) {
  if (preferredPrinter) {
    const exact = await qz.printers.find(preferredPrinter).catch(() => null)
    if (exact) return exact
  }
  const fallback = await qz.printers.getDefault().catch(() => null)
  if (fallback) return fallback
  throw new Error('No printer found. Set a default printer or configure VITE_THERMAL_PRINTER.')
}

async function directPrint() {
  if (!sale.value) return
  directPrintError.value = ''; directPrinting.value = true
  try {
    const html = buildDirectPrintHtml()
    if (!html) throw new Error('Receipt content is not ready yet.')
    const qz = await ensureQzConnected()
    const printer = await resolvePrinter(qz)
    const config = qz.configs.create(printer, {
      units: 'mm', copies: 1, scaleContent: true, rasterize: true,
      jobName: `Sale-${sale.value.invoice_number ?? sale.value.id}`,
    })
    await qz.print(config, [{ type: 'html', format: 'plain', data: html }])
  } catch (err) {
    directPrintError.value = `${err?.message || 'Direct print failed.'} Install/open QZ Tray, trust this site, then try again.`
  } finally {
    directPrinting.value = false
  }
}

watch(barcodeCanvas, () => drawAllBarcodes())

onMounted(async () => {
  try {
    const [saleRes, settingsRes] = await Promise.all([
      axios.get(`/api/sales/${route.params.id}`),
      axios.get('/api/shop-settings').catch(() => ({ data: {} })),
    ])
    sale.value = saleRes.data
    const s = settingsRes.data
    Object.keys(shop.value).forEach(k => { if (s[k] != null) shop.value[k] = s[k] })
    if (shop.value.print_mode) printMode.value = shop.value.print_mode
    await nextTick()
    drawAllBarcodes()
  } catch {
    sale.value = null
  } finally {
    loading.value = false
  }
})
</script>

<style>
/* â"€â"€ Screen: POS preview â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€ */
.receipt-paper {
  width: 287px;
  padding: 16px 14px;
  margin: 0 auto 32px;
  background: #fff;
  box-shadow: 0 0 0 1px #e5e7eb, 0 4px 24px rgba(0,0,0,0.08);
  border-radius: 4px;
  font-family: 'Courier New', Courier, monospace;
  font-size: 12px;
  line-height: 1.45;
  color: #111;
}
.receipt-divider        { border: none; border-top: 1px dashed #aaa; margin: 6px 0; }
.receipt-divider-solid  { border: none; border-top: 1px solid #555; margin: 6px 0; }
.receipt-divider-double { border: none; border-top: 3px double #333; margin: 6px 0; }

/* â"€â"€ Screen: A5 invoice preview â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€ */
.a5-paper {
  width: 148mm;
  min-height: 200mm;
  margin: 0 auto 32px;
  padding: 12mm 14mm;
  background: #fff;
  box-shadow: 0 0 0 1px #e5e7eb, 0 4px 32px rgba(0,0,0,0.10);
  border-radius: 4px;
  font-family: Arial, Helvetica, sans-serif;
  font-size: 11px;
  color: #111;
  box-sizing: border-box;
}
.inv-header       { display:flex; justify-content:space-between; align-items:flex-start; gap:12px; margin-bottom:12px; padding-bottom:10px; border-bottom:2px solid #1a1a1a; }
.inv-logo-block   { display:flex; align-items:flex-start; gap:10px; }
.inv-logo         { max-height:52px; max-width:80px; object-fit:contain; }
.inv-shop-name    { font-size:15px; font-weight:800; letter-spacing:0.5px; text-transform:uppercase; margin-bottom:2px; }
.inv-shop-sub     { font-size:10px; color:#555; line-height:1.5; }
.inv-meta-block   { text-align:right; min-width:140px; }
.inv-title        { font-size:22px; font-weight:900; letter-spacing:3px; color:#1a1a1a; margin-bottom:6px; }
.inv-meta-table   { font-size:10px; border-collapse:collapse; margin-left:auto; }
.inv-meta-table td { padding:1px 4px; }
.inv-meta-table td:first-child { color:#888; text-align:right; }
.inv-meta-table td:last-child  { font-size:11px; text-align:left; }
.inv-customer     { font-size:11px; background:#f9f9f9; border:1px solid #e5e7eb; padding:6px 10px; border-radius:4px; margin-bottom:10px; }
.inv-items-table  { width:100%; border-collapse:collapse; font-size:11px; margin-bottom:10px; }
.inv-items-table thead tr { background:#1a1a1a; color:#fff; }
.inv-items-table th { padding:5px 6px; font-size:10px; font-weight:700; letter-spacing:0.3px; }
.inv-items-table tbody tr { border-bottom:1px solid #e5e7eb; }
.inv-items-table tbody tr:nth-child(even) { background:#fafafa; }
.inv-items-table td { padding:5px 6px; vertical-align:top; }
.inv-totals-row   { display:flex; justify-content:space-between; align-items:flex-start; gap:16px; margin-top:8px; }
.inv-notes        { flex:1; font-size:10px; color:#555; padding-top:4px; }
.inv-totals       { min-width:220px; }
.inv-total-line   { display:flex; justify-content:space-between; font-size:11px; padding:3px 0; border-bottom:1px dashed #e5e7eb; }
.inv-grand-total  { font-size:14px; font-weight:800; border-top:2px solid #1a1a1a; border-bottom:2px solid #1a1a1a; padding:4px 0; margin:2px 0; }
.inv-footer       { text-align:center; margin-top:16px; padding-top:10px; border-top:1px dashed #ccc; font-size:11px; }

/* â"€â"€ @media print â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€ */
@media print {
  .no-print, aside, nav, header, footer { display: none !important; }
  html, body { margin:0 !important; padding:0 !important; height:auto !important; overflow:visible !important; background:#fff !important; }
  #app, #app > div, #app main { width:auto !important; min-width:0 !important; height:auto !important; min-height:0 !important; overflow:visible !important; padding:0 !important; margin:0 !important; background:#fff !important; }

  /* POS mode */
  [data-print-mode="pos"] #invoice-wrapper { display: none !important; }
  [data-print-mode="pos"] #receipt-wrapper { display:block !important; position:static !important; width:76mm !important; padding:0 !important; margin:0 auto !important; overflow:visible !important; }
  [data-print-mode="pos"] .receipt-paper { width:76mm !important; max-width:76mm !important; margin:0 !important; padding:4mm 3mm !important; box-shadow:none !important; border-radius:0 !important; font-size:11pt !important; font-family:'Courier New',Courier,monospace !important; color:#000 !important; background:#fff !important; }
  [data-print-mode="pos"] #receipt-wrapper, [data-print-mode="pos"] #receipt-wrapper * { color:#000 !important; -webkit-print-color-adjust:exact; print-color-adjust:exact; background:transparent !important; }

  /* A5 mode */
  [data-print-mode="a5"] #receipt-wrapper { display: none !important; }
  [data-print-mode="a5"] #invoice-wrapper { display: block !important; }
  [data-print-mode="a5"] .a5-paper { width:100% !important; min-height:0 !important; margin:0 !important; padding:0 !important; box-shadow:none !important; border-radius:0 !important; font-family:Arial,Helvetica,sans-serif !important; font-size:11pt !important; color:#000 !important; background:#fff !important; }
  [data-print-mode="a5"] .a5-paper * { -webkit-print-color-adjust:exact; print-color-adjust:exact; }
  [data-print-mode="a5"] .inv-items-table thead tr { background:#1a1a1a !important; color:#fff !important; }
}
</style>
