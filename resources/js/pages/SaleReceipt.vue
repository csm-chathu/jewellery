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
        <span v-if="sale?.sale_type === 'booking'" class="inline-flex items-center gap-1 text-xs font-semibold px-2 py-0.5 rounded-full"
          :class="sale.delivery_status === 'booked' ? 'bg-purple-100 text-purple-700' : 'bg-green-100 text-green-700'">
          {{ sale.delivery_status === 'booked' ? 'Booking — Pending Collection' : 'Booking — Collected' }}
        </span>
      </div>

      <div class="flex flex-wrap gap-2 items-center">
        <!-- Settle shortcut for pending bookings -->
        <button v-if="sale?.sale_type === 'booking' && sale?.delivery_status === 'booked'"
          @click="settleModal = true"
          class="inline-flex items-center gap-2 px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg font-medium text-sm shadow-sm transition-colors">
          <CheckCircleIcon class="w-4 h-4" /> Settle &amp; Deliver
        </button>

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


        <!-- Send SMS -->
        <button v-if="sale" @click="openSmsModal"
          class="inline-flex items-center gap-2 px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white rounded-lg font-medium text-sm shadow-sm transition-colors">
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
          </svg>
          Send SMS
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
           POS / 80mm Thermal Receipt (POS-80)
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
            <div v-if="sale.sale_type === 'booking'" style="text-align:center; font-weight:bold; font-size:11px; background:#111; color:#fff; padding:2px 0; margin-bottom:4px; letter-spacing:1px;">&#9733; BOOKING ADVANCE &#9733;</div>
            <div class="flex-row"><span>Invoice :</span><span style="font-weight:bold; float:right;">{{ sale.invoice_number }}</span></div>
            <div class="flex-row"><span>Date    :</span><span style="float:right;">{{ fmtDate(sale.sold_at) }}</span></div>
            <div class="flex-row"><span>Time    :</span><span style="float:right;">{{ formatTime(sale.sold_at) }}</span></div>
            <div class="flex-row"><span>Cashier :</span><span style="float:right;">{{ sale.user?.name ?? '&mdash;' }}</span></div>
            <div class="flex-row"><span>Payment :</span><span style="float:right; text-transform:capitalize;">{{ sale.payment_method?.replace('_',' ') }}</span></div>
            <div class="flex-row">
              <span>Status  :</span>
              <span style="float:right; font-weight:bold; text-transform:capitalize;">{{ sale.payment_status?.toUpperCase() }}</span>
            </div>
            <div v-if="sale.sale_type === 'booking' && sale.booking_expires_at" class="flex-row" style="color:#b91c1c; font-weight:bold;">
              <span>Expires :</span><span style="float:right;">{{ fmtDate(sale.booking_expires_at) }}</span>
            </div>
            <div v-if="sale.delivery_status === 'delivered' && sale.delivered_at" class="flex-row" style="color:#166534; font-weight:bold;">
              <span>Delivered:</span><span style="float:right;">{{ fmtDate(sale.delivered_at) }}</span>
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
                <span style="width:54px; text-align:right;">{{ lkr(item.display_price ?? item.unit_price) }}</span>
                <span style="width:58px; text-align:right; font-weight:bold;">{{ lkr(item.total) }}</span>
              </div>
              <div style="color:#555; font-size:9px; padding-left:2px; line-height:1.3;">
                <span v-if="item.product?.sku">SKU:{{ item.product.sku }}  </span>
                <span v-if="item.product?.karat">{{ item.product.karat }}</span>
                <span v-if="item.product?.weight"> {{ item.product.weight }}g</span>
              </div>
              <div v-if="item.making_charge || item.wastage_amount || item.gemstone_value"
                style="font-size:9px; color:#555; padding-left:2px;">
                <span v-if="item.gemstone_value">Gem:{{ lkr(item.gemstone_value) }}  </span>
                <span v-if="item.making_charge">MC:{{ lkr(item.making_charge) }}  </span>
                <span v-if="item.wastage_amount">Wst:{{ lkr(item.wastage_amount) }}</span>
              </div>
              <div v-if="Number(item.discount) > 0" style="font-size:9px; color:#555; padding-left:2px;">
                Item Disc: -{{ lkr(item.discount) }}
              </div>
              <div v-if="isAuditor && item.product?.purchase_price" style="font-size:9px; color:#b45309; padding-left:2px; font-weight:bold;">
                Cost: {{ lkr(item.product.purchase_price) }}
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

          <!-- FOOTER -->
          <div style="text-align:center; font-size:10px; line-height:1.6;">
            <div style="font-weight:bold;">*** Thank You! Come Again ***</div>
            <div v-if="whatsappQr" style="margin-top:4px;">
              <img :src="whatsappQr" alt="WhatsApp QR" style="width:80px; height:80px; display:inline-block;" />
              <div style="font-size:9px; color:#333; font-weight:bold; margin-top:2px;">Scan to WhatsApp us: 0764643050</div>
            </div>
            <div style="margin-top:6px; font-size:9px; color:#333; font-weight:bold; letter-spacing:0.5px;">www.lumac.lk</div>
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
            <!-- Left: Logo + Shop Name -->
            <div class="inv-logo-block">
              <img v-if="shop.logo_url" :src="shop.logo_url" alt="logo" class="inv-logo" />
              <div class="inv-shop-name">{{ shop.shop_name || appName }}</div>
            </div>

            <!-- Right: Shop contact + Invoice meta -->
            <div class="inv-meta-block">
              <div v-if="sale.sale_type === 'booking'" style="text-align:right; margin-bottom:6px;">
                <span style="background:#7c3aed; color:#fff; font-size:10px; font-weight:700; padding:2px 8px; border-radius:4px; letter-spacing:1px; text-transform:uppercase;">Booking Advance</span>
              </div>
              <table class="inv-meta-table">
                <tbody>
                  <tr><td>Invoice No</td><td><strong>{{ sale.invoice_number }}</strong></td></tr>
                  <tr><td>Date &amp; Time</td><td>{{ fmtDate(sale.sold_at) }}, {{ formatTime(sale.sold_at) }}</td></tr>
                  <tr><td>Cashier</td><td>{{ sale.user?.name ?? '&mdash;' }}</td></tr>
                  <tr v-if="sale.sale_type === 'booking' && sale.booking_expires_at">
                    <td style="color:#b91c1c;">Expires</td>
                    <td style="color:#b91c1c; font-weight:700;">{{ fmtDate(sale.booking_expires_at) }}</td>
                  </tr>
                  <tr v-if="sale.delivery_status === 'delivered' && sale.delivered_at">
                    <td style="color:#166534;">Delivered</td>
                    <td style="color:#166534; font-weight:700;">{{ fmtDate(sale.delivered_at) }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- SHOP CONTACT BAR -->
          <div v-if="shop.address || shop.phone || shop.br_number" class="inv-contact-bar">
            <span v-if="shop.address">{{ shop.address }}</span>
            <span v-if="shop.address && (shop.phone || shop.br_number)" class="inv-contact-sep">|</span>
            <span v-if="shop.phone">Tel: {{ shop.phone }}</span>
            <span v-if="shop.phone && shop.br_number" class="inv-contact-sep">|</span>
            <span v-if="shop.br_number">BR No: {{ shop.br_number }}</span>
          </div>

          <!-- CUSTOMER -->
          <div class="inv-customer" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:4px;">
            <span><strong>Bill To:</strong> {{ sale.customer?.name ?? 'Walk-in Customer' }}<span v-if="sale.customer?.phone"> &nbsp;|&nbsp; Tel: {{ sale.customer.phone }}</span></span>
            <span style="text-transform:capitalize;"><strong>Payment:</strong> {{ sale.payment_method?.replace('_', ' ') }}</span>
            <span><strong>Status:</strong> <span style="text-transform:uppercase;">{{ sale.payment_status }}</span></span>
          </div>

          <!-- ITEMS TABLE -->
          <table class="inv-items-table">
            <thead>
              <tr>
                <th style="text-align:left;">Item / Description</th>
                <th style="text-align:center; width:40px;">Qty</th>
                <th style="text-align:right; width:90px;">Unit Price</th>
                <th v-if="isAuditor" style="text-align:right; width:90px; color:#fbbf24;">Cost Price</th>
                <th style="text-align:right; width:80px;">Discount</th>
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
                  <div v-if="item.making_charge || item.wastage_amount || item.gemstone_value"
                    style="font-size:10px; color:#888; margin-top:2px;">
                    <span v-if="item.gemstone_value">Gem: LKR {{ lkr(item.gemstone_value) }}  </span>
                    <span v-if="item.making_charge">Making: LKR {{ lkr(item.making_charge) }}  </span>
                    <span v-if="item.wastage_amount">Wastage: LKR {{ lkr(item.wastage_amount) }}</span>
                  </div>
                </td>
                <td style="text-align:center;">{{ item.quantity }}</td>
                <td style="text-align:right;">{{ lkr(item.display_price ?? item.unit_price) }}</td>
                <td v-if="isAuditor" style="text-align:right; color:#b45309; font-weight:700;">
                  {{ item.product?.purchase_price ? lkr(item.product.purchase_price) : '—' }}
                </td>
                <td style="text-align:right; color:#dc2626;">
                  <span v-if="Number(item.discount) > 0">- {{ lkr(item.discount) }}</span>
                  <span v-else style="color:#ccc;">—</span>
                </td>
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
              <div v-if="Number(sale.discount) > 0" class="inv-total-line" style="color:#dc2626;">
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
            <div style="font-weight:600;">*** Thank You! Come Again ***</div>
            <div v-if="shop.shop_name" style="font-size:10px; color:#888; margin-top:2px;">{{ shop.shop_name }}</div>
            <div style="margin-top:6px; font-size:8px; color:#888; letter-spacing:0.5px;">www.lumac.lk &nbsp;|&nbsp; 076 464 3050</div>
          </div>

        </div>
      </div>

    </template>

    <!-- Error -->
    <div v-if="!loading && !sale" class="text-center py-20 text-gray-400">
      <p>Sale not found.</p>
      <router-link to="/sales" class="text-amber-600 hover:underline text-sm mt-2 inline-block">â† Back to Sales</router-link>
    </div>

    <!-- Settle Modal (accessible from receipt page for pending bookings) -->
    <teleport to="body">
      <div v-if="settleModal" class="fixed inset-0 z-50 bg-black/40 p-4 flex items-center justify-center no-print">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-md p-6 space-y-4">
          <h3 class="font-semibold text-gray-800 flex items-center gap-2">
            <CheckCircleIcon class="w-5 h-5 text-purple-600" /> Settle Booking &amp; Deliver
          </h3>
          <div class="bg-purple-50 border border-purple-200 rounded-lg p-3 text-sm">
            <div class="font-medium text-purple-900">{{ sale?.invoice_number }}</div>
            <div class="text-purple-700 mt-1">Remaining: <strong>LKR {{ settleRemaining }}</strong></div>
          </div>
          <div>
            <label class="form-label">Payment Method</label>
            <select v-model="settleForm.payment_method" class="form-input">
              <option value="cash">Cash</option>
              <option value="card">Card</option>
              <option value="bank_transfer">Bank Transfer</option>
              <option value="cheque">Cheque</option>
              <option value="other">Other</option>
            </select>
          </div>
          <div>
            <label class="form-label">Payment Amount (LKR)</label>
            <input v-model.number="settleForm.payment_amount" type="number" min="0" step="0.01" class="form-input" />
          </div>
          <div>
            <label class="form-label">Delivered At</label>
            <input v-model="settleForm.delivered_at" type="datetime-local" class="form-input" />
          </div>
          <p v-if="settleError" class="text-sm text-red-600 bg-red-50 px-3 py-2 rounded">{{ settleError }}</p>
          <div class="flex justify-end gap-2">
            <button @click="settleModal = false" class="px-4 py-2 text-sm text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-50">Cancel</button>
            <button @click="submitSettle" :disabled="settling" class="px-4 py-2 text-sm bg-purple-600 text-white rounded-lg hover:bg-purple-700 disabled:opacity-50 font-medium">
              {{ settling ? 'Posting…' : 'Settle & Deliver' }}
            </button>
          </div>
        </div>
      </div>
    </teleport>

    <!-- SMS Modal -->
    <teleport to="body">
      <div v-if="smsModal" class="fixed inset-0 z-50 bg-black/40 p-4 flex items-center justify-center no-print">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-md p-6 space-y-4">
          <h3 class="font-semibold text-gray-800 flex items-center gap-2">
            <svg class="w-5 h-5 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
            </svg>
            Send Receipt SMS
          </h3>

          <!-- Recipient -->
          <div>
            <label class="form-label">Recipient Phone Number</label>
            <input v-model="smsPhone" type="tel" placeholder="e.g. 0771234567"
              class="form-input" />
            <p v-if="sale?.customer?.name" class="text-xs text-gray-400 mt-1">
              Customer: {{ sale.customer.name }}
            </p>
          </div>

          <!-- Message -->
          <div>
            <label class="form-label">Message</label>
            <textarea v-model="smsMessage" rows="4"
              class="form-input resize-none text-sm font-mono"></textarea>
            <p class="text-xs text-gray-400 mt-1">{{ smsMessage.length }} characters</p>
          </div>

          <p v-if="smsError" class="text-sm text-red-600 bg-red-50 px-3 py-2 rounded">{{ smsError }}</p>
          <p v-if="smsSent" class="text-sm text-green-700 bg-green-50 px-3 py-2 rounded flex items-center gap-1.5">
            <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
            </svg>
            SMS sent successfully!
          </p>
          <div class="flex justify-end gap-2">
            <button @click="smsModal = false; smsSent = false" class="px-4 py-2 text-sm text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-50">Close</button>
            <button @click="submitSms" :disabled="sendingSms || smsSent || !smsPhone.trim()"
              class="px-4 py-2 text-sm bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 font-medium flex items-center gap-2">
              <svg v-if="sendingSms" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
              </svg>
              {{ sendingSms ? 'Sending…' : smsSent ? 'Sent' : 'Send SMS' }}
            </button>
          </div>
        </div>
      </div>
    </teleport>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch, nextTick } from 'vue'
import { useRoute } from 'vue-router'
import axios from 'axios'
import { ArrowLeftIcon, PrinterIcon, ArrowPathIcon, DocumentTextIcon, CheckCircleIcon } from '@heroicons/vue/24/outline'
import { fmtDate } from '../utils/date.js'
import QRCode from 'qrcode'
import { useAuthStore } from '../stores/auth.js'

const auth      = useAuthStore()
const isAuditor = computed(() => auth.user?.role === 'auditor')

const route           = useRoute()
const sale            = ref(null)
const loading         = ref(true)
const barcodeCanvas   = ref(null)
const appName         = import.meta.env.VITE_APP_NAME ?? 'Jewellery Store'
const preferredPrinter = import.meta.env.VITE_THERMAL_PRINTER ?? ''
const directPrinting  = ref(false)
const directPrintError = ref('')
const whatsappQr      = ref('')

// Settle booking
const settleModal  = ref(false)
const settling     = ref(false)
const settleError  = ref('')
const settleForm   = ref({ payment_method: 'cash', payment_amount: 0, delivered_at: '' })

// SMS
const smsModal    = ref(false)
const sendingSms  = ref(false)
const smsSent     = ref(false)
const smsError    = ref('')
const smsMessage  = ref('')
const smsPhone    = ref('')

function openSmsModal() {
  if (!sale.value) return
  smsSent.value = false
  smsError.value = ''
  smsPhone.value = sale.value.customer?.phone ?? ''
  const appUrl = window.location.origin
  const link = `${appUrl}/receipt/${sale.value.view_token}`
  const type = sale.value.sale_type === 'booking' ? 'Booking' : 'Invoice'
  const name = sale.value.customer?.name ?? 'Customer'
  smsMessage.value = `Dear ${name}, your ${type} ${sale.value.invoice_number} of LKR ${Number(sale.value.total).toLocaleString('en-LK', { minimumFractionDigits: 2 })} has been created. View receipt: ${link} . Thank you!`
  smsModal.value = true
}

async function submitSms() {
  sendingSms.value = true; smsError.value = ''
  try {
    await axios.post(`/api/sales/${sale.value.id}/send-sms`, {
      message: smsMessage.value,
      phone:   smsPhone.value.trim(),
    })
    smsSent.value = true
  } catch (e) {
    smsError.value = e.response?.data?.message ?? 'Failed to send SMS'
  } finally {
    sendingSms.value = false
  }
}

const settleRemaining = computed(() => {
  if (!sale.value) return '0.00'
  return Number(Math.max(0, Number(sale.value.total) - Number(sale.value.amount_paid)))
    .toLocaleString('en-LK', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
})

watch(settleModal, (open) => {
  if (open && sale.value) {
    settleForm.value = {
      payment_method: 'cash',
      payment_amount: Math.max(0, Number(sale.value.total) - Number(sale.value.amount_paid)),
      delivered_at: new Date().toISOString().slice(0, 16),
    }
    settleError.value = ''
  }
})

async function submitSettle() {
  settling.value = true
  settleError.value = ''
  try {
    const { data } = await axios.post(`/api/sales/${sale.value.id}/settle-booking`, settleForm.value)
    sale.value = data
    settleModal.value = false
    await nextTick()
  } catch (e) {
    settleError.value = e.response?.data?.message
      ?? Object.values(e.response?.data?.errors ?? {}).flat().join(', ')
      ?? 'Failed to settle booking'
  } finally {
    settling.value = false
  }
}

const shop = ref({ shop_name: '', address: '', phone: '', br_number: '', logo_url: '', print_mode: 'a5' })
const printMode = ref('a5')

function lkr(val) {
  return Number(val || 0).toLocaleString('en-LK', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}
function formatTime(d) {
  return new Date(d).toLocaleTimeString('en-LK', { hour: '2-digit', minute: '2-digit' })
}

async function printReceipt() {
  document.querySelector('#dyn-page-style')?.remove()
  const s = document.createElement('style')
  s.id = 'dyn-page-style'
  s.textContent = printMode.value === 'a5'
    ? `@media print { @page { size: A5; margin: 12mm 15mm; } }`
    : `@media print { @page { size: 80mm auto; margin: 0; } }`
  document.head.appendChild(s)

  if (window.electronAPI?.printReceipt) {
    await window.electronAPI.printReceipt(printMode.value) // 'pos' or 'a5'
    return
  }

  window.print()
}

function buildDirectPrintHtml() {
  const receipt = document.getElementById('receipt')
  if (!receipt) return ''
  const clonedReceipt = receipt.cloneNode(true)
  clonedReceipt.querySelector('.receipt-barcode')?.remove()
  return `<!doctype html><html><head><meta charset="utf-8">
<style>
  html,body{margin:0;padding:0;background:#fff;}
  .receipt-paper{width:80mm;max-width:80mm;margin:0;padding:3mm 4mm;box-sizing:border-box;
    font-family:'Courier New',Courier,monospace;font-size:14pt;line-height:1.5;font-weight:700;zoom:1.12;color:#000;background:#fff;}
  .receipt-divider{border:none;border-top:1px dashed #aaa;margin:6px 0;}
  .receipt-divider-solid{border:none;border-top:1px solid #555;margin:6px 0;}
  .receipt-divider-double{border:none;border-top:3px double #333;margin:6px 0;}
  .receipt-barcode{display:none !important;}
  canvas{max-width:100%;}
</style></head><body>${clonedReceipt.outerHTML}</body></html>`
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
  QRCode.toDataURL('https://wa.me/94764643050', { width: 120, margin: 1 })
    .then(url => { whatsappQr.value = url })
    .catch(() => {})

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
  font-size: 14px;
  line-height: 1.45;
  font-weight: 500;
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
.inv-logo-block   { display:flex; flex-direction:column; align-items:flex-start; gap:6px; }
.inv-logo         { max-height:70px; max-width:85px; object-fit:contain; flex-shrink:0; }
.inv-shop-name    { font-size:16px; font-weight:800; letter-spacing:0.5px; text-transform:uppercase; }
.inv-shop-sub     { font-size:10px; color:#555; line-height:1.6; }
.inv-meta-block   { text-align:right; min-width:160px; flex-shrink:0; }
.inv-contact-bar  { text-align:center; font-size:10px; color:#555; padding:5px 0 8px; border-bottom:1px solid #ddd; margin-bottom:10px; line-height:1.6; }
.inv-contact-sep  { margin:0 6px; color:#bbb; }
.inv-title        { font-size:22px; font-weight:900; letter-spacing:3px; color:#1a1a1a; margin-bottom:6px; }
.inv-meta-table   { font-size:10px; border-collapse:collapse; margin-left:auto; }
.inv-meta-table td { padding:3px 6px; }
.inv-meta-table td:first-child { color:#888; text-align:right; white-space:nowrap; }
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
.inv-grand-total  { font-size:11px; font-weight:800; border-top:2px solid #1a1a1a; border-bottom:2px solid #1a1a1a; padding:4px 0; margin:2px 0; }
.inv-footer       { text-align:center; margin-top:16px; padding-top:10px; border-top:1px dashed #ccc; font-size:11px; }

/* â"€â"€ @media print â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€ */
@media print {
  .no-print, aside, nav, header, footer { display: none !important; }
  html, body { margin:0 !important; padding:0 !important; height:auto !important; overflow:visible !important; background:#fff !important; }
  #app, #app > div, #app main { width:auto !important; min-width:0 !important; height:auto !important; min-height:0 !important; overflow:visible !important; padding:0 !important; margin:0 !important; background:#fff !important; }

  /* POS mode */
  [data-print-mode="pos"] #invoice-wrapper { display: none !important; }
  [data-print-mode="pos"] #receipt-wrapper { display:block !important; position:static !important; width:80mm !important; padding:0 !important; margin:0 !important; overflow:visible !important; }
  [data-print-mode="pos"] .receipt-paper { width:80mm !important; max-width:80mm !important; margin:0 !important; padding:3mm 4mm !important; box-shadow:none !important; border-radius:0 !important; font-size:14pt !important; font-family:'Courier New',Courier,monospace !important; font-weight:700 !important; line-height:1.5 !important; zoom:1.12 !important; color:#000 !important; background:#fff !important; }
  [data-print-mode="pos"] #receipt-wrapper, [data-print-mode="pos"] #receipt-wrapper * { color:#000 !important; -webkit-print-color-adjust:exact; print-color-adjust:exact; background:transparent !important; }

  /* A5 mode */
  [data-print-mode="a5"] #receipt-wrapper { display: none !important; }
  [data-print-mode="a5"] #invoice-wrapper { display: block !important; }
  [data-print-mode="a5"] .a5-paper { width:100% !important; min-height:0 !important; margin:0 !important; padding:0 !important; box-shadow:none !important; border-radius:0 !important; font-family:Arial,Helvetica,sans-serif !important; font-size:11pt !important; color:#000 !important; background:#fff !important; }
  [data-print-mode="a5"] .a5-paper * { -webkit-print-color-adjust:exact; print-color-adjust:exact; }
  [data-print-mode="a5"] .inv-items-table thead tr { background:#1a1a1a !important; color:#fff !important; }
}
</style>
