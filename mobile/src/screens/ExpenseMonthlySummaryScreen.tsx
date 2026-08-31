import { MaterialIcons } from "@expo/vector-icons";
import { useNavigation, useFocusEffect } from "@react-navigation/native";
import type { NativeStackNavigationProp } from "@react-navigation/native-stack";
import { useCallback, useState } from "react";
import {
  ActivityIndicator,
  FlatList,
  StyleSheet,
  Text,
  TouchableOpacity,
  View,
} from "react-native";
import { SafeAreaView } from "react-native-safe-area-context";

import ScreenHeader from "../components/ScreenHeader";

import { getMonthlyExpenses, type MonthlyExpenseSummary } from "../api/expense";
import type { AppStackParamList } from "../navigation/RootNavigator";

type Nav = NativeStackNavigationProp<AppStackParamList, "ExpenseMonthlySummary">;

export default function ExpenseMonthlySummaryScreen() {
  const navigation = useNavigation<Nav>();
  const [summaries, setSummaries] = useState<MonthlyExpenseSummary[]>([]);
  const [loading, setLoading] = useState(true);

  const fetchSummaries = async () => {
    setLoading(true);
    try {
      const res = await getMonthlyExpenses();
      if (res.success) {
        setSummaries(res.data);
      }
    } catch (e) {
      console.warn("Failed to fetch monthly summaries", e);
    } finally {
      setLoading(false);
    }
  };

  useFocusEffect(
    useCallback(() => {
      fetchSummaries();
    }, [])
  );

  const renderItem = ({ item }: { item: MonthlyExpenseSummary }) => {
    // Convert '2026-08' to 'August 2026'
    const [year, month] = item.month.split('-');
    const date = new Date(Number(year), Number(month) - 1);
    const monthName = date.toLocaleString('default', { month: 'long', year: 'numeric' });

    return (
      <TouchableOpacity 
        style={styles.card}
        activeOpacity={0.8}
        onPress={() => navigation.navigate("ExpenseStatement", { month: item.month })}
      >
        <View style={styles.cardHeader}>
          <Text style={styles.monthText}>{monthName}</Text>
          <MaterialIcons name="chevron-right" size={24} color="#7367F0" />
        </View>
        
        <View style={styles.cardBody}>
          <Text style={styles.label}>Total Approved Expenses</Text>
          <Text style={styles.amountText}>₹{Number(item.total_approved).toFixed(2)}</Text>
        </View>
      </TouchableOpacity>
    );
  };

  return (
    <View style={{ flex: 1, backgroundColor: "#F3F4F6" }}>
      <ScreenHeader
        title="Monthly Expense Summary"
        onBack={() => navigation.goBack()}
      />

      {loading ? (
        <View style={{ flex: 1, justifyContent: "center" }}>
          <ActivityIndicator size="large" color="#7367F0" />
        </View>
      ) : (
        <FlatList
          data={summaries}
          keyExtractor={(item) => item.month}
          contentContainerStyle={styles.listContent}
          renderItem={renderItem}
          ListEmptyComponent={
            <Text style={styles.emptyText}>No approved expenses found.</Text>
          }
        />
      )}
    </View>
  );
}

const styles = StyleSheet.create({
  safe: { flex: 1, backgroundColor: "#F3F4F6" },
  header: {
    backgroundColor: "#7367F0",
    paddingHorizontal: 14,
    paddingVertical: 14,
    flexDirection: "row",
    alignItems: "center",
  },
  headerIconBtn: {
    width: 40,
    height: 40,
    borderRadius: 14,
    alignItems: "center",
    justifyContent: "center",
  },
  headerTitle: {
    flex: 1,
    textAlign: "center",
    color: "#fff",
    fontSize: 18,
    fontWeight: "800",
  },
  listContent: {
    padding: 16,
  },
  card: {
    backgroundColor: "#fff",
    borderRadius: 16,
    padding: 20,
    marginBottom: 16,
    elevation: 3,
    shadowColor: "#000",
    shadowOpacity: 0.05,
    shadowRadius: 5,
    borderLeftWidth: 4,
    borderLeftColor: "#7367F0",
  },
  cardHeader: {
    flexDirection: "row",
    justifyContent: "space-between",
    alignItems: "center",
    marginBottom: 12,
  },
  monthText: {
    fontSize: 16,
    fontWeight: "800",
    color: "#111827",
  },
  cardBody: {
    alignItems: "flex-start",
  },
  label: {
    fontSize: 12,
    fontWeight: "600",
    color: "#6B7280",
    marginBottom: 4,
  },
  amountText: {
    fontSize: 28,
    fontWeight: "900",
    color: "#10B981", // Green to signify approved
  },
  emptyText: {
    textAlign: "center",
    marginTop: 40,
    color: "#6B7280",
    fontSize: 14,
    fontWeight: "600",
  },
});
