import { MaterialCommunityIcons, MaterialIcons } from "@expo/vector-icons";
import { useNavigation, useFocusEffect } from "@react-navigation/native";
import type { NativeStackNavigationProp } from "@react-navigation/native-stack";
import { useCallback, useState } from "react";
import {
  ActivityIndicator,
  FlatList,
  Image,
  StyleSheet,
  Text,
  TouchableOpacity,
  View,
} from "react-native";
import { SafeAreaView } from "react-native-safe-area-context";

import ScreenHeader from "../components/ScreenHeader";

import { getExpenses, type ExpenseBill } from "../api/expense";
import type { AppStackParamList } from "../navigation/RootNavigator";

type Nav = NativeStackNavigationProp<AppStackParamList, "ExpenseList">;

export default function ExpenseListScreen() {
  const navigation = useNavigation<Nav>();
  const [expenses, setExpenses] = useState<ExpenseBill[]>([]);
  const [loading, setLoading] = useState(true);
  const [filterStatus, setFilterStatus] = useState<'all' | 'pending' | 'approved' | 'rejected'>('all');

  const fetchExpenses = async () => {
    setLoading(true);
    try {
      const res = await getExpenses();
      if (res.success) {
        setExpenses(res.data);
      }
    } catch (e) {
      console.warn("Failed to fetch expenses", e);
    } finally {
      setLoading(false);
    }
  };

  useFocusEffect(
    useCallback(() => {
      fetchExpenses();
    }, [])
  );

  const renderExpenseItem = ({ item }: { item: ExpenseBill }) => {
    const isPending = item.status === 'pending';
    const isApproved = item.status === 'approved';

    return (
      <View style={styles.card}>
        <View style={styles.cardHeader}>
          <View style={{ flexDirection: 'row', alignItems: 'center' }}>
            <MaterialCommunityIcons 
              name={item.is_system_generated ? "car-connected" : "receipt"} 
              size={20} 
              color={item.is_system_generated ? "#7367F0" : "#F59E0B"} 
            />
            <Text style={styles.categoryName}>
              {item.category?.name || (item.is_system_generated ? "Auto Travel" : "Unknown")}
            </Text>
          </View>
          <Text
            style={[
              styles.statusBadge,
              isPending && styles.statusPending,
              isApproved && styles.statusApproved,
              !isPending && !isApproved && styles.statusRejected,
            ]}
          >
            {item.status.toUpperCase()}
          </Text>
        </View>

        <View style={styles.cardBody}>
          <Text style={styles.amountText}>₹{Number(item.amount).toFixed(2)}</Text>
          <View style={{ alignItems: 'flex-end' }}>
            <Text style={styles.dateText}>
              {item.date ? new Date(item.date).toLocaleDateString() : new Date(item.created_at).toLocaleDateString()}
            </Text>
            {item.is_after_day_end && (
              <Text style={{ fontSize: 10, color: '#D97706', fontWeight: '700', marginTop: 2 }}>
                ⚠️ Post Day-End
              </Text>
            )}
          </View>
        </View>
      </View>
    );
  };

  return (
    <View style={{ flex: 1, backgroundColor: "#F3F4F6" }}>
      <ScreenHeader
        title="My Expenses"
        onBack={() => navigation.goBack()}
        rightElement={
          <TouchableOpacity
            style={{ width: 36, height: 36, alignItems: "center", justifyContent: "center" }}
            onPress={() => navigation.navigate("AddExpense")}
          >
            <MaterialIcons name="add" size={24} color="#fff" />
          </TouchableOpacity>
        }
      />

      {/* Filter Tabs */}
      <View style={styles.tabsContainer}>
        {(['all', 'pending', 'approved', 'rejected'] as const).map(status => (
          <TouchableOpacity 
            key={status} 
            style={[styles.tab, filterStatus === status && styles.activeTab]}
            onPress={() => setFilterStatus(status)}
          >
            <Text style={[styles.tabText, filterStatus === status && styles.activeTabText]}>
              {status.toUpperCase()}
            </Text>
          </TouchableOpacity>
        ))}
      </View>

      {loading ? (
        <View style={{ flex: 1, justifyContent: "center" }}>
          <ActivityIndicator size="large" color="#7367F0" />
        </View>
      ) : (
        <FlatList
          data={expenses.filter(e => filterStatus === 'all' || e.status === filterStatus)}
          keyExtractor={(item) => item.id.toString()}
          contentContainerStyle={styles.listContent}
          renderItem={renderExpenseItem}
          ListEmptyComponent={
            <Text style={styles.emptyText}>No expenses found.</Text>
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
  headerAddBtn: {
    width: 40,
    height: 40,
    alignItems: "center",
    justifyContent: "center",
  },
  tabsContainer: {
    flexDirection: 'row',
    backgroundColor: '#fff',
    paddingHorizontal: 10,
    paddingVertical: 8,
    elevation: 2,
    shadowColor: '#000',
    shadowOpacity: 0.05,
    shadowRadius: 3,
    justifyContent: 'space-between'
  },
  tab: {
    paddingVertical: 6,
    paddingHorizontal: 12,
    borderRadius: 20,
    backgroundColor: '#F3F4F6',
  },
  activeTab: {
    backgroundColor: '#7367F0',
  },
  tabText: {
    fontSize: 11,
    fontWeight: '700',
    color: '#6B7280',
  },
  activeTabText: {
    color: '#fff',
  },
  listContent: {
    padding: 16,
  },
  card: {
    backgroundColor: "#fff",
    borderRadius: 16,
    padding: 16,
    marginBottom: 12,
    elevation: 3,
    shadowColor: "#000",
    shadowOpacity: 0.05,
    shadowRadius: 5,
  },
  cardHeader: {
    flexDirection: "row",
    justifyContent: "space-between",
    alignItems: "center",
    marginBottom: 12,
  },
  categoryName: {
    fontSize: 14,
    fontWeight: "700",
    color: "#374151",
    marginLeft: 8,
  },
  statusBadge: {
    paddingHorizontal: 8,
    paddingVertical: 4,
    borderRadius: 8,
    fontSize: 10,
    fontWeight: "800",
    color: "#fff",
    overflow: "hidden",
  },
  statusPending: { backgroundColor: "#F59E0B" },
  statusApproved: { backgroundColor: "#10B981" },
  statusRejected: { backgroundColor: "#EF4444" },
  cardBody: {
    flexDirection: "row",
    justifyContent: "space-between",
    alignItems: "flex-end",
  },
  amountText: {
    fontSize: 20,
    fontWeight: "900",
    color: "#111827",
  },
  dateText: {
    fontSize: 12,
    fontWeight: "600",
    color: "#9CA3AF",
  },
  emptyText: {
    textAlign: "center",
    marginTop: 40,
    color: "#6B7280",
    fontSize: 14,
    fontWeight: "600",
  },
});
