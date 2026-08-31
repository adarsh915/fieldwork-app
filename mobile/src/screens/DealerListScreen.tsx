import { MaterialCommunityIcons, MaterialIcons } from "@expo/vector-icons";
import { useNavigation, useFocusEffect } from "@react-navigation/native";
import type { NativeStackNavigationProp } from "@react-navigation/native-stack";
import { useMemo, useState, useEffect, useCallback } from "react";
import {
  ActivityIndicator,
  Alert,
  Modal,
  ScrollView,
  StyleSheet,
  Text,
  TextInput,
  TouchableOpacity,
  View,
} from "react-native";
import { SafeAreaView } from "react-native-safe-area-context";

import ScreenHeader from "../components/ScreenHeader";
import { getMasterParties, type Party } from "../api/master";
import AppBottomBar from "../components/AppBottomBar";
import type { RootStackParamList } from "../navigation/RootNavigator";

type Nav = NativeStackNavigationProp<RootStackParamList, "DealerList">;

const ACTIONS: {
  label: string;
  icon: keyof typeof MaterialCommunityIcons.glyphMap;
}[] = [
    { label: "Ledger", icon: "clipboard-text-outline" },
    { label: "Visit", icon: "map-marker-outline" },
    { label: "Order", icon: "cart-outline" },
    { label: "Payment", icon: "currency-inr" },
    { label: "Find Location", icon: "crosshairs-gps" },
    { label: "Party History", icon: "history" },
    { label: "Contact Dealer", icon: "account-box-outline" },
    { label: "Add Reminder", icon: "alarm" },
    { label: "History", icon: "file-document-outline" },
    { label: "Bill", icon: "receipt" },
  ];

export default function DealerListScreen() {
  const navigation = useNavigation<Nav>();
  const [query, setQuery] = useState("");
  const [menuDealer, setMenuDealer] = useState<Party | null>(null);

  const [parties, setParties] = useState<Party[]>([]);
  const [loading, setLoading] = useState(true);

  useFocusEffect(
    useCallback(() => {
      let isActive = true;
      setLoading(true);
      getMasterParties()
        .then((res) => {
          if (isActive && res.success) {
            setParties(res.data);
          }
        })
        .catch((err) => {
          console.error(err);
          Alert.alert("Error", "Failed to load parties.");
        })
        .finally(() => {
          if (isActive) setLoading(false);
        });

      return () => {
        isActive = false;
      };
    }, []),
  );

  const filtered = useMemo(() => {
    const q = query.trim().toLowerCase();
    if (!q) return parties;
    return parties.filter(
      (d) =>
        d.name.toLowerCase().includes(q) ||
        (d.mobile && d.mobile.toLowerCase().includes(q)),
    );
  }, [parties, query]);

  return (
    <View style={{ flex: 1, backgroundColor: "#EDEDED" }}>
      <ScreenHeader
        title="Dealer List"
        onBack={() => navigation.goBack()}
        rightElement={
          <View style={{ flexDirection: "row", alignItems: "center" }}>
            <TouchableOpacity style={{ width: 36, height: 36, alignItems: "center", justifyContent: "center" }}>
              <MaterialIcons name="notifications-none" size={24} color="#FFFFFF" />
            </TouchableOpacity>
            <TouchableOpacity style={{ width: 36, height: 36, alignItems: "center", justifyContent: "center" }}>
              <MaterialIcons name="access-time" size={22} color="#FFFFFF" />
            </TouchableOpacity>
          </View>
        }
      />

      <ScrollView contentContainerStyle={styles.scrollContent}>
        <View style={styles.searchWrap}>
          <TextInput
            style={styles.searchInput}
            placeholder="Search"
            placeholderTextColor="#8E8E8E"
            value={query}
            onChangeText={setQuery}
          />
          <MaterialIcons name="search" size={22} color="#7367F0" />
        </View>

        <View style={styles.totalsCard}>
          <View style={styles.totalCol}>
            <Text style={[styles.totalValue, { color: "#f59e0b" }]}>
              {filtered.length}
            </Text>
            <Text style={[styles.totalLabel, { color: "#f59e0b" }]}>
              Total Dealers
            </Text>
          </View>
          <View style={styles.totalDivider} />
          <View style={styles.totalCol}>
            <Text style={[styles.totalValue, { color: "#7367F0" }]}>0</Text>
            <Text style={[styles.totalLabel, { color: "#7367F0" }]}>
              Total Credit
            </Text>
          </View>
          <View style={styles.totalDivider} />
          <View style={styles.totalCol}>
            <Text style={[styles.totalValue, { color: "#ef4444" }]}>0</Text>
            <Text style={[styles.totalLabel, { color: "#ef4444" }]}>
              Total Debt
            </Text>
          </View>
          <View style={styles.totalProgress}>
            <View
              style={[styles.progressSeg, { backgroundColor: "#f59e0b" }]}
            />
            <View
              style={[styles.progressSeg, { backgroundColor: "#7367F0" }]}
            />
            <View
              style={[styles.progressSeg, { backgroundColor: "#ef4444" }]}
            />
          </View>
        </View>

        {loading ? (
          <ActivityIndicator
            size="large"
            color="#7367F0"
            style={{ marginTop: 40 }}
          />
        ) : filtered.length === 0 ? (
          <Text
            style={{
              textAlign: "center",
              marginTop: 40,
              color: "#8e8e8e",
              fontWeight: "bold",
            }}
          >
            No parties found.
          </Text>
        ) : (
          filtered.map((dealer) => (
            <View key={dealer.id} style={styles.dealerCard}>
              <View style={styles.dealerAccent} />
              <View style={styles.dealerBody}>
                <View style={styles.dealerHeaderRow}>
                  <View style={{ flex: 1 }}>
                    <Text style={styles.dealerTitle}>{dealer.name}</Text>
                    {dealer.approval_status === "pending" && (
                      <View style={styles.pendingBadge}>
                        <MaterialCommunityIcons name="clock-outline" size={10} color="#D97706" />
                        <Text style={styles.pendingBadgeText}>Pending Approval</Text>
                      </View>
                    )}
                  </View>
                  {dealer.approval_status !== "pending" && (
                    <TouchableOpacity
                      style={styles.menuBtn}
                      activeOpacity={0.85}
                      onPress={() => setMenuDealer(dealer)}
                    >
                      <MaterialCommunityIcons
                        name="dots-vertical"
                        size={18}
                        color="#7367F0"
                      />
                    </TouchableOpacity>
                  )}
                </View>

                {dealer.mobile ? (
                  <View style={styles.dealerRow}>
                    <MaterialCommunityIcons
                      name="phone"
                      size={16}
                      color="#111827"
                    />
                    <Text style={styles.dealerText}>{dealer.mobile}</Text>
                  </View>
                ) : null}

                <Text style={styles.dealerTextBold}>
                  {dealer.address || "No address"}
                </Text>

                <View style={styles.dealerFooterRow}>
                  <Text style={styles.balance}>
                    <Text style={{ color: "#ef4444" }}>Bal :</Text>{" "}
                    <Text style={{ color: "#16a34a" }}>0Db</Text>
                  </Text>
                  {dealer.mobile ? (
                    <MaterialCommunityIcons
                      name="whatsapp"
                      size={18}
                      color="#16a34a"
                    />
                  ) : null}
                  <Text style={styles.clText}>CL:0</Text>
                </View>
              </View>
            </View>
          ))
        )}
      </ScrollView>

      <TouchableOpacity
        style={styles.fab}
        activeOpacity={0.9}
        onPress={() => navigation.navigate("AddParty")}
      >
        <MaterialIcons name="add" size={28} color="#FFFFFF" />
      </TouchableOpacity>

      <AppBottomBar
        activeKey="Home"
        onTabPress={(key) => {
          if (key === "Home") navigation.replace("Dashboard");
        }}
      />

      <DealerMenuModal
        visible={!!menuDealer}
        dealer={menuDealer}
        onDismiss={() => setMenuDealer(null)}
        onAction={(action, dealer) => {
          if (action === "Visit") {
            navigation.navigate("Visit", {
              partyId: dealer.id,
              partyName: dealer.name,
              partyMobile: dealer.mobile,
              partyAddress: dealer.address,
            });
          } else {
            Alert.alert(
              "Coming Soon",
              `${action} functionality is coming soon!`,
            );
          }
        }}
      />
    </View>
  );
}

function DealerMenuModal({
  visible,
  dealer,
  onDismiss,
  onAction,
}: {
  visible: boolean;
  dealer: Party | null;
  onDismiss: () => void;
  onAction: (action: string, dealer: Party) => void;
}) {
  return (
    <Modal visible={visible} animationType="fade" transparent>
      <TouchableOpacity
        style={styles.menuOverlay}
        activeOpacity={1}
        onPress={onDismiss}
      >
        <View style={styles.menuCard}>
          {ACTIONS.map((a) => (
            <TouchableOpacity
              key={a.label}
              style={styles.menuRow}
              activeOpacity={0.85}
              onPress={() => {
                onDismiss();
                if (dealer) onAction(a.label, dealer);
              }}
            >
              <MaterialCommunityIcons name={a.icon} size={22} color="#7367F0" />
              <Text style={styles.menuText}>{a.label}</Text>
            </TouchableOpacity>
          ))}
        </View>
      </TouchableOpacity>
    </Modal>
  );
}

const styles = StyleSheet.create({
  safe: {
    flex: 1,
    backgroundColor: "#EDEDED",
  },
  header: {
    backgroundColor: "#7367F0",
    paddingHorizontal: 14,
    paddingVertical: 16,
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
    color: "#FFFFFF",
    fontSize: 18,
    fontWeight: "800",
  },
  headerRight: {
    flexDirection: "row",
    alignItems: "center",
  },
  scrollContent: {
    padding: 14,
    paddingBottom: 130,
  },
  searchWrap: {
    backgroundColor: "#FFFFFF",
    borderRadius: 16,
    borderWidth: 1,
    borderColor: "#7367F0",
    paddingHorizontal: 14,
    height: 52,
    flexDirection: "row",
    alignItems: "center",
    justifyContent: "space-between",
    marginBottom: 12,
  },
  searchInput: {
    flex: 1,
    fontSize: 16,
    fontWeight: "800",
    color: "#7367F0",
  },
  totalsCard: {
    backgroundColor: "#FFFFFF",
    borderRadius: 16,
    paddingTop: 14,
    paddingBottom: 10,
    flexDirection: "row",
    alignItems: "center",
    justifyContent: "space-between",
    shadowColor: "#000",
    shadowOpacity: 0.08,
    shadowRadius: 12,
    elevation: 6,
    overflow: "hidden",
    marginBottom: 12,
  },
  totalCol: {
    flex: 1,
    alignItems: "center",
    paddingHorizontal: 4,
  },
  totalValue: {
    fontWeight: "900",
    fontSize: 18,
  },
  totalLabel: {
    marginTop: 2,
    fontWeight: "800",
    fontSize: 11,
  },
  totalDivider: {
    width: 1,
    alignSelf: "stretch",
    backgroundColor: "#EDEDED",
  },
  totalProgress: {
    position: "absolute",
    left: 0,
    right: 0,
    bottom: 0,
    height: 4,
    flexDirection: "row",
  },
  progressSeg: {
    flex: 1,
  },
  dealerCard: {
    backgroundColor: "#FFFFFF",
    borderRadius: 14,
    overflow: "hidden",
    flexDirection: "row",
    marginBottom: 12,
    shadowColor: "#000",
    shadowOpacity: 0.06,
    shadowRadius: 10,
    elevation: 4,
  },
  dealerAccent: {
    width: 6,
    backgroundColor: "#ef4444",
  },
  dealerBody: {
    flex: 1,
    padding: 12,
  },
  dealerHeaderRow: {
    flexDirection: "row",
    alignItems: "flex-start",
    justifyContent: "space-between",
    marginBottom: 8,
  },
  dealerTitle: {
    flex: 1,
    fontWeight: "900",
    fontSize: 12,
    color: "#111827",
    paddingRight: 10,
  },
  menuBtn: {
    width: 28,
    height: 28,
    borderRadius: 14,
    alignItems: "center",
    justifyContent: "center",
  },
  dealerRow: {
    flexDirection: "row",
    alignItems: "center",
    gap: 6,
    marginBottom: 4,
  },
  dealerText: {
    fontWeight: "800",
    fontSize: 12,
    color: "#111827",
  },
  dealerTextBold: {
    fontWeight: "800",
    fontSize: 11,
    color: "#111827",
    marginBottom: 2,
  },
  dealerFooterRow: {
    marginTop: 6,
    flexDirection: "row",
    alignItems: "center",
    gap: 10,
  },
  balance: {
    fontWeight: "900",
    fontSize: 12,
  },
  clText: {
    marginLeft: "auto",
    fontWeight: "900",
    fontSize: 12,
    color: "#111827",
  },
  fab: {
    position: "absolute",
    right: 18,
    bottom: 100,
    width: 56,
    height: 56,
    borderRadius: 28,
    backgroundColor: "#7367F0",
    alignItems: "center",
    justifyContent: "center",
    shadowColor: "#7367F0",
    shadowOpacity: 0.25,
    shadowRadius: 16,
    elevation: 10,
  },
  menuOverlay: {
    flex: 1,
    backgroundColor: "rgba(0,0,0,0.05)",
    justifyContent: "center",
    alignItems: "center",
  },
  menuCard: {
    width: 240,
    backgroundColor: "#FFFFFF",
    borderRadius: 12,
    paddingVertical: 10,
    shadowColor: "#000",
    shadowOpacity: 0.12,
    shadowRadius: 18,
    elevation: 10,
  },
  menuRow: {
    flexDirection: "row",
    alignItems: "center",
    gap: 12,
    paddingVertical: 10,
    paddingHorizontal: 14,
  },
  menuText: {
    fontWeight: "800",
    fontSize: 14,
    color: "#111827",
  },
  pendingBadge: {
    flexDirection: "row",
    alignItems: "center",
    backgroundColor: "#FEF3C7",
    paddingHorizontal: 6,
    paddingVertical: 2,
    borderRadius: 4,
    alignSelf: "flex-start",
    marginTop: 4,
    gap: 3,
  },
  pendingBadgeText: {
    color: "#D97706",
    fontSize: 9,
    fontWeight: "700",
  },
});
