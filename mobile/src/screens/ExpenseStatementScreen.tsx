import { MaterialIcons } from "@expo/vector-icons";
import { useNavigation, useRoute, RouteProp } from "@react-navigation/native";
import { useCallback, useState, useEffect } from "react";
import {
  ActivityIndicator,
  ScrollView,
  StyleSheet,
  Text,
  TouchableOpacity,
  View,
} from "react-native";
import { SafeAreaView } from "react-native-safe-area-context";

import ScreenHeader from "../components/ScreenHeader";

import { getMonthlyStatement, type ExpenseStatementResponse } from "../api/expense";
import type { AppStackParamList } from "../navigation/RootNavigator";

type NavRoute = RouteProp<AppStackParamList, "ExpenseStatement">;

export default function ExpenseStatementScreen() {
  const navigation = useNavigation();
  const route = useRoute<NavRoute>();
  const { month } = route.params;

  const [data, setData] = useState<ExpenseStatementResponse["data"] | null>(null);
  const [loading, setLoading] = useState(true);

  // Convert "2026-08" to "August 2026"
  const [yearStr, monthStr] = month.split('-');
  const displayMonth = new Date(Number(yearStr), Number(monthStr) - 1)
    .toLocaleString('default', { month: 'long', year: 'numeric' });

  const fetchStatement = useCallback(async () => {
    setLoading(true);
    try {
      const res = await getMonthlyStatement(month);
      if (res.success) {
        setData(res.data);
      }
    } catch (e) {
      console.warn("Failed to fetch statement", e);
    } finally {
      setLoading(false);
    }
  }, [month]);

  // Load data once
  useEffect(() => {
    fetchStatement();
  }, [fetchStatement]);

  const formatMoney = (amount: number | string) => {
    const num = Number(amount);
    if (num === 0) return "-";
    return `₹${num.toFixed(2)}`;
  };


  return (
    <View style={{ flex: 1, backgroundColor: "#F3F4F6" }}>
      <ScreenHeader
        title={`Expense Statement - ${displayMonth}`}
        onBack={() => navigation.goBack()}
      />

      {loading ? (
        <View style={{ flex: 1, justifyContent: "center" }}>
          <ActivityIndicator size="large" color="#7367F0" />
        </View>
      ) : data ? (
        <View style={{ flex: 1 }}>
          {/* Summary Card at the top */}
          <View style={styles.summaryCard}>
            <Text style={styles.summaryTitle}>TOTAL REIMBURSEMENT</Text>
            <Text style={styles.summaryAmount}>{formatMoney(data.totals.grand_total)}</Text>
            
            <View style={styles.summaryGrid}>
              <View style={styles.summaryItem}>
                <Text style={styles.summaryLabel}>Auto Travel</Text>
                <Text style={[styles.summaryVal, { color: '#7367F0' }]}>{formatMoney(data.totals.system_travel)}</Text>
              </View>
              <View style={styles.summaryItem}>
                <Text style={styles.summaryLabel}>Food</Text>
                <Text style={styles.summaryVal}>{formatMoney(data.totals.food)}</Text>
              </View>
              <View style={styles.summaryItem}>
                <Text style={styles.summaryLabel}>Hotel</Text>
                <Text style={styles.summaryVal}>{formatMoney(data.totals.hotel)}</Text>
              </View>
              <View style={styles.summaryItem}>
                <Text style={styles.summaryLabel}>Travel</Text>
                <Text style={styles.summaryVal}>{formatMoney(data.totals.travel)}</Text>
              </View>
              <View style={styles.summaryItem}>
                <Text style={styles.summaryLabel}>Misc</Text>
                <Text style={styles.summaryVal}>{formatMoney(data.totals.misc)}</Text>
              </View>
              <View style={styles.summaryItem}>
                <Text style={styles.summaryLabel}>Other</Text>
                <Text style={styles.summaryVal}>{formatMoney(data.totals.other)}</Text>
              </View>
            </View>
          </View>

          <Text style={styles.tableTitle}>Daily Breakdown</Text>
          
          <View style={styles.tableContainer}>
            <ScrollView horizontal showsHorizontalScrollIndicator={true}>
              <View>
                {/* Header Row */}
                <View style={[styles.row, styles.headerRow]}>
                  <Text style={[styles.cell, styles.headerCell, styles.dateCell]}>DATE</Text>
                  <Text style={[styles.cell, styles.headerCell, styles.amountCell]}>FOOD</Text>
                  <Text style={[styles.cell, styles.headerCell, styles.amountCell]}>HOTEL</Text>
                  <Text style={[styles.cell, styles.headerCell, styles.amountCell]}>TRAVEL</Text>
                  <Text style={[styles.cell, styles.headerCell, styles.amountCell]}>MISC</Text>
                  <Text style={[styles.cell, styles.headerCell, styles.amountCell, { color: '#7367F0' }]}>AUTO TRAVEL</Text>
                  <Text style={[styles.cell, styles.headerCell, styles.amountCell, { color: '#111827' }]}>DAY TOTAL</Text>
                </View>

                <ScrollView showsVerticalScrollIndicator={false}>
                  {data.statementData.map((row, idx) => (
                    <View key={idx} style={[styles.row, idx % 2 === 0 ? styles.evenRow : styles.oddRow]}>
                      <Text style={[styles.cell, styles.dateCell, styles.boldCell]}>{row.date}</Text>
                      <Text style={[styles.cell, styles.amountCell]}>{formatMoney(row.food)}</Text>
                      <Text style={[styles.cell, styles.amountCell]}>{formatMoney(row.hotel)}</Text>
                      <Text style={[styles.cell, styles.amountCell]}>{formatMoney(row.travel)}</Text>
                      <Text style={[styles.cell, styles.amountCell]}>{formatMoney(Number(row.misc) + Number(row.other))}</Text>
                      <Text style={[styles.cell, styles.amountCell, styles.autoTravelText]}>{formatMoney(row.system_travel)}</Text>
                      <Text style={[styles.cell, styles.amountCell, styles.dayTotalText]}>{formatMoney(row.day_total)}</Text>
                    </View>
                  ))}
                  {/* Give some padding at the bottom for scrolling */}
                  <View style={{ height: 20 }} />
                </ScrollView>
              </View>
            </ScrollView>
          </View>
        </View>
      ) : (
        <View style={{ flex: 1, justifyContent: "center", alignItems: "center" }}>
          <Text style={{ color: "#6B7280" }}>No data found.</Text>
        </View>
      )}
    </View>
  );
}

const styles = StyleSheet.create({
  safe: { flex: 1, backgroundColor: "#F3F4F6" },
  header: {
    backgroundColor: "#7367F0",
    paddingHorizontal: 14,
    paddingVertical: 10,
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
    color: "#fff",
    fontSize: 18,
    fontWeight: "800",
  },
  headerSub: {
    color: "#E0E7FF",
    fontSize: 12,
    fontWeight: "600",
  },
  
  // Summary Card
  summaryCard: {
    backgroundColor: '#fff',
    margin: 10,
    padding: 16,
    borderRadius: 12,
    alignItems: 'center',
    elevation: 4,
    shadowColor: '#000',
    shadowOpacity: 0.1,
    shadowRadius: 5,
    borderTopWidth: 4,
    borderTopColor: '#10B981', // Green for total
  },
  summaryTitle: {
    fontSize: 12,
    fontWeight: '700',
    color: '#6B7280',
    letterSpacing: 1,
    marginBottom: 4,
  },
  summaryAmount: {
    fontSize: 32,
    fontWeight: '900',
    color: '#111827',
    marginBottom: 16,
  },
  summaryGrid: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    width: '100%',
    justifyContent: 'space-between',
    borderTopWidth: 1,
    borderTopColor: '#F3F4F6',
    paddingTop: 12,
  },
  summaryItem: {
    width: '30%',
    alignItems: 'center',
    marginBottom: 10,
  },
  summaryLabel: {
    fontSize: 10,
    fontWeight: '600',
    color: '#9CA3AF',
    textTransform: 'uppercase',
    marginBottom: 2,
  },
  summaryVal: {
    fontSize: 13,
    fontWeight: '800',
    color: '#374151',
  },

  tableTitle: {
    marginLeft: 14,
    marginTop: 8,
    fontSize: 13,
    fontWeight: '700',
    color: '#4B5563',
    textTransform: 'uppercase',
  },
  tableContainer: {
    flex: 1,
    backgroundColor: '#fff',
    margin: 10,
    borderRadius: 8,
    overflow: 'hidden',
    elevation: 2,
    shadowColor: '#000',
    shadowOpacity: 0.05,
    shadowRadius: 3,
  },
  row: {
    flexDirection: 'row',
    borderBottomWidth: 1,
    borderBottomColor: '#F3F4F6',
  },
  headerRow: {
    backgroundColor: '#F9FAFB',
    borderBottomWidth: 2,
    borderBottomColor: '#E5E7EB',
  },
  evenRow: {
    backgroundColor: '#fff',
  },
  oddRow: {
    backgroundColor: '#F9FAFB',
  },
  cell: {
    paddingVertical: 12,
    paddingHorizontal: 10,
    justifyContent: 'center',
    fontSize: 13,
    color: '#4B5563',
  },
  headerCell: {
    fontWeight: '700',
    fontSize: 11,
    color: '#6B7280',
  },
  dateCell: {
    width: 100,
  },
  amountCell: {
    width: 80,
    textAlign: 'right',
  },
  boldCell: {
    fontWeight: '700',
    color: '#111827',
  },
  autoTravelText: {
    color: '#7367F0',
    fontWeight: '600',
  },
  dayTotalText: {
    color: '#111827',
    fontWeight: '800',
  },
});
