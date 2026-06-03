<script setup lang="ts">
import { computed, ref, watch } from "vue";
import type { InvoiceStatus } from "~/types/invoice";
import type { InvoiceWithRefs } from "~/services/useInvoices";
import { useToast } from "~/composables/useToast";
import Modal from "~/components/ui/Modal.vue";
import Pill from "~/components/ui/Pill.vue";
import Button from "~/components/ui/Button.vue";
import Select from "~/components/ui/Select.vue";
import Icon from "~/components/ui/Icon.vue";

const props = defineProps<{
  open: boolean;
  row: InvoiceWithRefs | null;
}>();

const emit = defineEmits<{
  "update:open": [value: boolean];
  paid: [];
}>();

const { t } = useI18n();
const { show } = useToast();
const { formatRM } = useMoney();

const paying = ref(false);
const bank = ref("maybank");

// FPX participating banks — proper nouns, not translated.
const bankOptions = [
  { value: "maybank", label: "Maybank2u" },
  { value: "cimb", label: "CIMB Clicks" },
  { value: "public", label: "Public Bank" },
  { value: "rhb", label: "RHB Now" },
  { value: "hongleong", label: "Hong Leong Connect" },
  { value: "bankislam", label: "Bank Islam" },
  { value: "ambank", label: "AmOnline" },
];

const statusToneMap = {
  pending: "pending",
  paid: "paid",
  overdue: "overdue",
  cancelled: "cancelled",
} as const satisfies Record<InvoiceStatus, string>;

const formatDate = (iso: string) => {
  if (!iso) return "—";
  const [y, m, d] = iso.split("-");
  return `${d}/${m}/${y}`;
};

const total = computed(() => {
  const inv = props.row?.invoice;
  return inv ? inv.amount + inv.lateFee : 0;
});

const periodLabel = computed(() => {
  const due = props.row?.invoice.dueDate;
  if (!due) return "";
  return new Date(due).toLocaleString("en-MY", {
    month: "long",
    year: "numeric",
  });
});

const isPayable = computed(
  () =>
    props.row?.invoice.status === "pending" ||
    props.row?.invoice.status === "overdue",
);

const onPay = async () => {
  if (!props.row) return;
  paying.value = true;
  try {
    // Demo: stand in for the FPX redirect round-trip.
    await new Promise((r) => setTimeout(r, 900));
    await useInvoices().recordPayment({
      invoiceId: props.row.invoice.id,
      amount: total.value,
      method: "fpx",
      paidAt: new Date().toISOString(),
      reference: `FPX-${bank.value.toUpperCase()}-${Date.now().toString().slice(-8)}`,
    });
    show(t("tenant.payments.payModal.successToast"), "success");
    emit("paid");
    emit("update:open", false);
  } finally {
    paying.value = false;
  }
};

watch(
  () => props.open,
  (isOpen) => {
    if (isOpen) bank.value = "maybank";
  },
);
</script>

<template>
  <Modal
    :open="open"
    :title="row ? t('tenant.payments.payModal.title') : ''"
    :description="row ? row.invoice.invoiceNumber : undefined"
    size="md"
    @update:open="emit('update:open', $event)"
  >
    <div v-if="row" class="space-y-5">
      <div class="flex items-start justify-between gap-3">
        <div>
          <div class="text-caption text-ink-muted">
            {{ t("tenant.payments.payModal.period") }}
          </div>
          <div class="text-body text-ink">{{ periodLabel }}</div>
          <div class="mt-1 text-caption text-ink-muted tabular-nums">
            {{ t("tenant.payments.dueOn", { date: formatDate(row.invoice.dueDate) }) }}
          </div>
        </div>
        <Pill :tone="statusToneMap[row.invoice.status]">
          {{ t(`tenant.payments.status.${row.invoice.status}`) }}
        </Pill>
      </div>

      <section
        class="rounded-md border border-line-passive bg-surface-page p-4"
      >
        <dl class="space-y-2 text-body">
          <div class="flex items-baseline justify-between">
            <dt class="text-ink-muted">
              {{ t("tenant.payments.payModal.rent") }}
            </dt>
            <dd class="tabular-nums text-ink">
              {{ formatRM(row.invoice.amount) }}
            </dd>
          </div>
          <div
            v-if="row.invoice.lateFee > 0"
            class="flex items-baseline justify-between"
          >
            <dt class="text-status-overdue">
              {{ t("tenant.payments.payModal.lateFee") }}
            </dt>
            <dd class="tabular-nums text-status-overdue">
              {{ formatRM(row.invoice.lateFee) }}
            </dd>
          </div>
          <div
            class="flex items-baseline justify-between border-t border-line-passive pt-2"
          >
            <dt class="font-semibold text-ink">
              {{ t("tenant.payments.payModal.total") }}
            </dt>
            <dd
              class="text-card-title font-semibold tabular-nums text-ink"
            >
              {{ formatRM(total) }}
            </dd>
          </div>
        </dl>
      </section>

      <!-- Payable: show the FPX picker -->
      <section v-if="isPayable" class="space-y-3">
        <Select
          v-model="bank"
          :options="bankOptions"
          :label="t('tenant.payments.payModal.bank')"
        />
        <p class="flex items-center gap-1.5 text-micro text-ink-faint">
          <Icon name="Info" :size="12" class="shrink-0" />
          {{ t("tenant.payments.payModal.simNote") }}
        </p>
      </section>

      <!-- Settled: show the receipt -->
      <section v-else-if="row.payments.length > 0">
        <div
          class="text-caption font-semibold uppercase tracking-wide text-ink-muted"
        >
          {{ t("tenant.payments.payModal.receipt") }}
        </div>
        <ul class="mt-2 divide-y divide-line-passive">
          <li
            v-for="p in row.payments"
            :key="p.id"
            class="flex items-baseline justify-between py-2 text-caption"
          >
            <div>
              <span class="text-ink">
                {{ t(`tenant.payments.methods.${p.method}`) }}
              </span>
              <span class="ml-2 text-ink-muted tabular-nums">
                {{ formatDate(p.paidAt.slice(0, 10)) }}
              </span>
              <span v-if="p.reference" class="ml-2 text-ink-faint">
                · {{ p.reference }}
              </span>
            </div>
            <span class="tabular-nums text-ink">{{ formatRM(p.amount) }}</span>
          </li>
        </ul>
      </section>
    </div>

    <template #footer>
      <Button
        variant="ghost"
        :disabled="paying"
        @click="emit('update:open', false)"
      >
        {{ t("common.close") }}
      </Button>
      <Button
        v-if="isPayable"
        variant="primary"
        :loading="paying"
        @click="onPay"
      >
        <Icon v-if="!paying" name="CreditCard" :size="14" class="mr-1" />
        {{
          paying
            ? t("tenant.payments.payModal.processing")
            : t("tenant.payments.payModal.payCta", { amount: formatRM(total) })
        }}
      </Button>
    </template>
  </Modal>
</template>
