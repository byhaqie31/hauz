<script setup lang="ts">
import { computed, onMounted, ref } from "vue";
import Card from "~/components/ui/Card.vue";
import Pill from "~/components/ui/Pill.vue";
import Icon from "~/components/ui/Icon.vue";
import EmptyState from "~/components/ui/EmptyState.vue";
import AgreementDocumentsPanel from "~/components/owner/AgreementDocumentsPanel.vue";
import type { AgreementWithRefs } from "~/services/useAgreements";

definePageMeta({ layout: "tenant" });
const { t } = useI18n();
const { formatRM } = useMoney();
const { tenantId } = useTenantSession();
const { public: { features } } = useRuntimeConfig();
const documentsEnabled = features.documents;
useHead({ title: () => t("tenant.nav.agreement") });

const row = ref<AgreementWithRefs | null>(null);
const loading = ref(true);

onMounted(async () => {
  try {
    if (tenantId.value) {
      row.value = await useAgreements().getActiveForTenant(tenantId.value);
    }
  } finally {
    loading.value = false;
  }
});

const dayMs = 24 * 60 * 60 * 1000;
const termSummary = computed(() => {
  if (!row.value) return "";
  const start = new Date(row.value.agreement.startDate).getTime();
  const end = new Date(row.value.agreement.endDate).getTime();
  const months = Math.round((end - start) / dayMs / 30);
  if (months >= 12 && months % 12 === 0) {
    return t("tenant.agreement.years", { n: months / 12 });
  }
  return t("tenant.agreement.months", { n: months });
});

const formatDate = (iso: string) => {
  if (!iso) return "—";
  const [y, m, d] = iso.split("-");
  return `${d}/${m}/${y}`;
};
</script>

<template>
  <div>
    <header class="mb-6 sm:mb-8">
      <h1 class="text-display-sub font-semibold tracking-snug">
        {{ t("tenant.agreement.title") }}
      </h1>
      <p class="mt-1 text-caption text-ink-muted">
        {{ t("tenant.agreement.subtitle") }}
      </p>
    </header>

    <Card v-if="loading" padding="loose">
      <p class="text-center text-body text-ink-muted">{{ t("common.loading") }}</p>
    </Card>

    <Card v-else-if="!row" padding="loose">
      <EmptyState
        icon="FileText"
        :title="t('tenant.home.noAgreementTitle')"
        :description="t('tenant.agreement.none')"
      />
    </Card>

    <div v-else class="space-y-4 sm:space-y-6">
      <!-- Summary -->
      <Card padding="loose">
        <div class="mb-5 flex flex-wrap items-center gap-2">
          <Pill :tone="row.agreement.status">
            {{ t(`tenant.agreement.status.${row.agreement.status}`) }}
          </Pill>
          <span class="text-caption text-ink-muted">
            <Icon name="Building2" :size="12" class="mr-1 inline" />
            {{ row.property?.name ?? "—" }} · {{ row.unit?.label ?? "—" }}
          </span>
        </div>

        <div class="grid grid-cols-2 gap-3 sm:grid-cols-4 sm:gap-4">
          <div class="rounded-md border border-line-passive bg-surface-page p-4">
            <div class="text-caption text-ink-muted">
              {{ t("tenant.agreement.term") }}
            </div>
            <div class="mt-1 text-card-title font-semibold tabular-nums text-ink">
              {{ termSummary }}
            </div>
          </div>
          <div class="rounded-md border border-line-passive bg-surface-page p-4">
            <div class="text-caption text-ink-muted">
              {{ t("tenant.agreement.monthlyRent") }}
            </div>
            <div class="mt-1 text-card-title font-semibold tabular-nums text-ink">
              {{ formatRM(row.agreement.rentAmount) }}
            </div>
          </div>
          <div class="rounded-md border border-line-passive bg-surface-page p-4">
            <div class="text-caption text-ink-muted">
              {{ t("tenant.agreement.deposit") }}
            </div>
            <div class="mt-1 text-card-title font-semibold tabular-nums text-ink">
              {{ formatRM(row.agreement.depositAmount) }}
            </div>
          </div>
          <div class="rounded-md border border-line-passive bg-surface-page p-4">
            <div class="text-caption text-ink-muted">
              {{ t("tenant.agreement.dueDay") }}
            </div>
            <div class="mt-1 text-card-title font-semibold tabular-nums text-ink">
              {{ t("tenant.agreement.dueOn", { day: row.agreement.rentDueDay }) }}
            </div>
          </div>
        </div>

        <section class="mt-6 space-y-3">
          <h3 class="text-caption font-semibold uppercase tracking-wide text-ink-muted">
            {{ t("tenant.agreement.term") }}
          </h3>
          <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
            <div class="rounded-md border border-line-passive bg-surface-page p-4">
              <div class="text-caption text-ink-muted">
                {{ t("tenant.agreement.startDate") }}
              </div>
              <div class="mt-1 text-body font-medium tabular-nums text-ink">
                {{ formatDate(row.agreement.startDate) }}
              </div>
            </div>
            <div class="rounded-md border border-line-passive bg-surface-page p-4">
              <div class="text-caption text-ink-muted">
                {{ t("tenant.agreement.endDate") }}
              </div>
              <div class="mt-1 text-body font-medium tabular-nums text-ink">
                {{ formatDate(row.agreement.endDate) }}
              </div>
            </div>
          </div>
        </section>

        <section class="mt-6 space-y-3">
          <h3 class="text-caption font-semibold uppercase tracking-wide text-ink-muted">
            {{ t("tenant.agreement.money") }}
          </h3>
          <div class="rounded-md border border-line-passive bg-surface-page p-4">
            <div class="flex items-baseline justify-between py-1 text-body">
              <span class="text-ink-muted">{{ t("tenant.agreement.lateFee") }}</span>
              <span class="tabular-nums text-ink">
                {{ formatRM(row.agreement.lateFee) }}
              </span>
            </div>
          </div>
          <p class="text-micro text-ink-faint">
            <Icon name="Info" :size="12" class="mr-1 inline" />
            {{ t("tenant.agreement.lateFeeHint") }}
          </p>
        </section>
      </Card>

      <!-- Documents (Phase-4 placeholder) -->
      <Card v-if="documentsEnabled" padding="loose">
        <h2 class="mb-4 text-card-title font-semibold text-ink">
          {{ t("tenant.agreement.documents") }}
        </h2>
        <AgreementDocumentsPanel />
      </Card>
    </div>
  </div>
</template>
