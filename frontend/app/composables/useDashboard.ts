import { computed, ref } from "vue";
import { propertiesMock } from "~/mocks/properties";
import { unitsMock } from "~/mocks/units";
import { tenantsMock } from "~/mocks/tenants";
import { agreementsMock } from "~/mocks/agreements";
import { invoicesMock, paymentsMock } from "~/mocks/invoices";
import { ticketsMock } from "~/mocks/tickets";

const DAY_MS = 24 * 60 * 60 * 1000;

const ymKey = (d: Date) =>
  `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, "0")}`;

export type AttentionKind =
  | "overdue"
  | "expiring"
  | "notice_given"
  | "ticket_new"
  | "ticket_reopened";

export interface AttentionItem {
  kind: AttentionKind;
  title: string;
  meta: string;
  link: string;
}

export interface DashboardStats {
  monthlyIncome: number; // sen
  occupancyPct: number;
  occupiedCount: number;
  unitCount: number;
  outstanding: number; // sen
  outstandingCount: number;
  expiringCount: number;
}

/** Raw server bucket — the localized label is derived on the client. */
interface IncomeBucket {
  key: string; // YYYY-MM
  amount: number; // sen
}

export interface MonthlyBucket extends IncomeBucket {
  label: string; // localized short month name
}

/** The exact `GET /api/dashboard` payload (also built from mocks in demo). */
export interface DashboardData {
  isEmpty: boolean;
  stats: DashboardStats;
  incomeSeries: IncomeBucket[];
  needsAttention: AttentionItem[];
}

const EMPTY_STATS: DashboardStats = {
  monthlyIncome: 0,
  occupancyPct: 0,
  occupiedCount: 0,
  unitCount: 0,
  outstanding: 0,
  outstandingCount: 0,
  expiringCount: 0,
};

/**
 * Builds the dashboard payload from the in-memory mocks — the demo/mock
 * mirror of the server-side `DashboardController`. Kept in lock-step with it.
 */
const buildFromMocks = (): DashboardData => {
  const isEmpty = propertiesMock.length === 0;

  const unitCount = unitsMock.length;
  const occupiedCount = unitsMock.filter((u) => u.status === "occupied").length;
  const occupancyPct =
    unitCount > 0 ? Math.round((occupiedCount / unitCount) * 100) : 0;

  const outstandingInvoices = invoicesMock.filter(
    (i) => i.status === "pending" || i.status === "overdue",
  );
  const outstanding = outstandingInvoices.reduce(
    (sum, i) => sum + i.amount + i.lateFee,
    0,
  );
  const outstandingCount = outstandingInvoices.length;

  const now = new Date();
  const thisMonth = ymKey(now);
  const successful = paymentsMock.filter((p) => p.status === "successful");
  const monthlyIncome = successful
    .filter((p) => ymKey(new Date(p.paidAt)) === thisMonth)
    .reduce((sum, p) => sum + p.amount, 0);

  const series: IncomeBucket[] = [];
  for (let i = 11; i >= 0; i--) {
    const d = new Date(now.getFullYear(), now.getMonth() - i, 1);
    series.push({ key: ymKey(d), amount: 0 });
  }
  successful.forEach((p) => {
    const bucket = series.find((s) => s.key === ymKey(new Date(p.paidAt)));
    if (bucket) bucket.amount += p.amount;
  });

  const nowMs = now.getTime();
  const expiringAgreements = agreementsMock.filter((a) => {
    if (a.status !== "active") return false;
    const end = new Date(a.endDate).getTime();
    return end >= nowMs && end - nowMs <= 60 * DAY_MS;
  });

  const needsAttention: AttentionItem[] = [];

  invoicesMock
    .filter((i) => i.status === "overdue")
    .forEach((inv) => {
      const ag = agreementsMock.find((a) => a.id === inv.agreementId);
      const tenant = ag ? tenantsMock.find((t) => t.id === ag.tenantId) : null;
      needsAttention.push({
        kind: "overdue",
        title: inv.invoiceNumber,
        meta: tenant?.name ?? "—",
        link: "/owner/payments",
      });
    });

  expiringAgreements.forEach((a) => {
    const tenant = tenantsMock.find((t) => t.id === a.tenantId);
    const daysLeft = Math.ceil((new Date(a.endDate).getTime() - nowMs) / DAY_MS);
    needsAttention.push({
      kind: "expiring",
      title: tenant?.name ?? "Agreement",
      meta: `${daysLeft}d`,
      link: "/owner/agreements",
    });
  });

  tenantsMock
    .filter((t) => t.status === "notice_given")
    .forEach((t) => {
      needsAttention.push({
        kind: "notice_given",
        title: t.name,
        meta: "",
        link: `/owner/tenants/${t.id}`,
      });
    });

  ticketsMock
    .filter((t) => t.status === "new")
    .filter((t) => t.priority === "high" || t.priority === "urgent")
    .forEach((t) => {
      needsAttention.push({
        kind: "ticket_new",
        title: t.title,
        meta: t.priority,
        link: `/owner/maintenance/${t.id}`,
      });
    });

  ticketsMock
    .filter((t) => t.status === "reopened")
    .forEach((t) => {
      needsAttention.push({
        kind: "ticket_reopened",
        title: t.title,
        meta: t.priority,
        link: `/owner/maintenance/${t.id}`,
      });
    });

  return {
    isEmpty,
    stats: {
      monthlyIncome,
      occupancyPct,
      occupiedCount,
      unitCount,
      outstanding,
      outstandingCount,
      expiringCount: expiringAgreements.length,
    },
    incomeSeries: series,
    needsAttention,
  };
};

/**
 * Owner dashboard state. A single `getDashboard()` fetch replaces the six
 * separate list calls the composable used to make — the server (or the mock
 * builder) computes stats, the income series, and the attention feed. The
 * only client-side work left is localizing month labels.
 */
export const useDashboard = () => {
  const { useMock } = useEnv();

  const loading = ref(true);
  const data = ref<DashboardData | null>(null);

  const getDashboard = async () => {
    loading.value = true;
    try {
      data.value = useMock
        ? buildFromMocks()
        : await useApi().request<DashboardData>("/dashboard");
    } finally {
      loading.value = false;
    }
  };

  const isEmpty = computed(() => data.value?.isEmpty ?? true);
  const stats = computed<DashboardStats>(() => data.value?.stats ?? EMPTY_STATS);
  const needsAttention = computed<AttentionItem[]>(
    () => data.value?.needsAttention ?? [],
  );

  // Localize month labels client-side so the server payload stays locale-free.
  const monthlyIncomeSeries = computed<MonthlyBucket[]>(() =>
    (data.value?.incomeSeries ?? []).map((b) => ({
      key: b.key,
      amount: b.amount,
      label: new Date(`${b.key}-01`).toLocaleDateString("en-MY", {
        month: "short",
      }),
    })),
  );

  return {
    getDashboard,
    loading,
    isEmpty,
    stats,
    needsAttention,
    monthlyIncomeSeries,
  };
};
