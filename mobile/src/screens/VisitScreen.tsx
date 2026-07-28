import { MaterialCommunityIcons, MaterialIcons } from "@expo/vector-icons";
import {
  useNavigation,
  useRoute,
  useFocusEffect,
} from "@react-navigation/native";
import type { RouteProp } from "@react-navigation/native";
import type { NativeStackNavigationProp } from "@react-navigation/native-stack";
import { useCallback, useState } from "react";
import {
  ActivityIndicator,
  Alert,
  Modal,
  ScrollView,
  StyleSheet,
  Text,
  TouchableOpacity,
  View,
} from "react-native";
import { SafeAreaView } from "react-native-safe-area-context";

import {
  checkIn,
  checkOut,
  getPartyVisitStatus,
  type Visit,
} from "../api/visit";
import type { AppStackParamList } from "../navigation/RootNavigator";

type Nav = NativeStackNavigationProp<AppStackParamList, "Visit">;
type RouteType = RouteProp<AppStackParamList, "Visit">;

export default function VisitScreen() {
  const navigation = useNavigation<Nav>();
  const route = useRoute<RouteType>();
  const { partyId, partyName, partyMobile, partyAddress } = route.params;

  const [loading, setLoading] = useState(true);
  const [submitting, setSubmitting] = useState(false);
  const [activeVisit, setActiveVisit] = useState<Visit | null>(null);
  const [hasOtherVisit, setHasOtherVisit] = useState(false);
  const [showTxMenu, setShowTxMenu] = useState(false);

  // Fetch visit status for this party
  const loadStatus = useCallback(() => {
    setLoading(true);
    getPartyVisitStatus(partyId)
      .then((res) => {
        if (res.success) {
          setActiveVisit(res.active_visit);
          setHasOtherVisit(res.has_other_active_visit);
        }
      })
      .catch((err) => {
        // if day not started it will throw
        if (err.status === 400 || err.status === 404) {
          Alert.alert(
            "Notice",
            err.message || "You need to start your day first.",
            [{ text: "OK", onPress: () => navigation.goBack() }],
          );
        }
      })
      .finally(() => setLoading(false));
  }, [partyId, navigation]);

  useFocusEffect(
    useCallback(() => {
      loadStatus();
    }, [loadStatus]),
  );

  const handleCheckIn = async () => {
    if (hasOtherVisit) {
      Alert.alert(
        "Notice",
        "You have another ongoing visit. Please check out of that first.",
      );
      return;
    }

    setSubmitting(true);
    try {
      // In a real app, you would get GPS coords here
      const res = await checkIn({ party_id: partyId });
      if (res.success) {
        setActiveVisit(res.data);
      }
    } catch (error: any) {
      Alert.alert("Error", error.message || "Check-in failed");
    } finally {
      setSubmitting(false);
    }
  };

  const handleCheckOut = async () => {
    if (!activeVisit) return;

    setSubmitting(true);
    try {
      // In a real app, you would get GPS coords here
      const res = await checkOut(activeVisit.id, {
        remarks: "Visit completed",
      });
      if (res.success) {
        setActiveVisit(null);
        Alert.alert("Success", "Checked out successfully.", [
          { text: "OK", onPress: () => navigation.goBack() },
        ]);
      }
    } catch (error: any) {
      Alert.alert("Error", error.message || "Check-out failed");
    } finally {
      setSubmitting(false);
    }
  };

  const handleTxOption = (option: string) => {
    setShowTxMenu(false);
    if (option === "Order" && activeVisit) {
      navigation.navigate("Order", { visitId: activeVisit.id, partyId });
    } else {
      Alert.alert("Coming Soon", `${option} functionality will be built next.`);
    }
  };

  if (loading) {
    return (
      <SafeAreaView style={styles.safe}>
        <ActivityIndicator
          size="large"
          color="#7367F0"
          style={{ marginTop: 40 }}
        />
      </SafeAreaView>
    );
  }

  const isCheckedIn = !!activeVisit;

  return (
    <SafeAreaView style={styles.safe}>
      {/* Header */}
      <View style={styles.header}>
        <TouchableOpacity
          style={styles.headerIconBtn}
          onPress={() => navigation.goBack()}
        >
          <MaterialIcons name="arrow-back-ios-new" size={20} color="#fff" />
        </TouchableOpacity>
        <Text style={styles.headerTitle}>Visit details</Text>
        <View style={{ width: 40 }} />
      </View>

      <ScrollView
        contentContainerStyle={styles.scrollContent}
        // Close menu if user scrolls
        onScrollBeginDrag={() => setShowTxMenu(false)}
      >
        {/* Party Info Card with Check In Button */}
        <View style={styles.userCard}>
          <View style={styles.userInfo}>
            <Text style={styles.partyName}>{partyName}</Text>
            {partyMobile && (
              <View style={styles.row}>
                <MaterialCommunityIcons name="phone" size={16} color="#666" />
                <Text style={styles.partyDetail}>{partyMobile}</Text>
              </View>
            )}
            <View style={styles.row}>
              <MaterialCommunityIcons
                name="map-marker-outline"
                size={16}
                color="#666"
              />
              <Text style={styles.partyDetail}>
                {partyAddress || "No address provided"}
              </Text>
            </View>

            {hasOtherVisit && !isCheckedIn && (
              <Text style={styles.warningText}>
                You have another active visit.
              </Text>
            )}
            {isCheckedIn && activeVisit?.check_in_at && (
              <Text style={styles.infoText}>
                Checked in:{" "}
                {new Date(activeVisit.check_in_at).toLocaleTimeString([], {
                  hour: "2-digit",
                  minute: "2-digit",
                })}
              </Text>
            )}
          </View>

          <TouchableOpacity
            style={[
              styles.compactBtn,
              isCheckedIn ? styles.checkOutBtn : styles.checkInBtn,
              submitting && { opacity: 0.7 },
            ]}
            onPress={isCheckedIn ? handleCheckOut : handleCheckIn}
            disabled={submitting}
          >
            {submitting ? (
              <ActivityIndicator color="#fff" size="small" />
            ) : (
              <>
                <MaterialCommunityIcons
                  name={isCheckedIn ? "logout" : "login"}
                  size={22}
                  color="#fff"
                />
                <Text style={styles.compactBtnText}>
                  {isCheckedIn ? "CHECK\nOUT" : "CHECK\nIN"}
                </Text>
              </>
            )}
          </TouchableOpacity>
        </View>

        {/* Order History Placeholder */}
        <View style={styles.card}>
          <Text style={styles.sectionTitle}>Order History</Text>
          <View style={styles.emptyState}>
            <MaterialCommunityIcons
              name="clipboard-text-outline"
              size={32}
              color="#ccc"
            />
            <Text style={styles.emptyStateText}>No recent orders</Text>
          </View>
        </View>

        {/* Tech History Placeholder */}
        <View style={styles.card}>
          <Text style={styles.sectionTitle}>Tech / Remarks History</Text>
          <View style={styles.emptyState}>
            <MaterialCommunityIcons name="history" size={32} color="#ccc" />
            <Text style={styles.emptyStateText}>No remarks found</Text>
          </View>
        </View>
      </ScrollView>

      {/* Bottom Action Buttons */}
      <View style={styles.bottomBar}>
        <TouchableOpacity
          style={styles.bottomBtn}
          disabled={!isCheckedIn}
          onPress={() => setShowTxMenu(!showTxMenu)}
        >
          <MaterialCommunityIcons
            name="currency-inr"
            size={24}
            color={isCheckedIn ? "#fff" : "rgba(255,255,255,0.5)"}
          />
          <Text
            style={[
              styles.bottomBtnText,
              !isCheckedIn && { color: "rgba(255,255,255,0.5)" },
            ]}
          >
            Transaction
          </Text>
        </TouchableOpacity>
        <View style={styles.divider} />
        <TouchableOpacity style={styles.bottomBtn} disabled={!isCheckedIn}>
          <MaterialCommunityIcons
            name="bank-transfer"
            size={24}
            color={isCheckedIn ? "#fff" : "rgba(255,255,255,0.5)"}
          />
          <Text
            style={[
              styles.bottomBtnText,
              !isCheckedIn && { color: "rgba(255,255,255,0.5)" },
            ]}
          >
            A/C Transaction
          </Text>
        </TouchableOpacity>
      </View>

      {/* Transaction Overlay Menu (Rendered via Modal for perfect overlay) */}
      <Modal
        visible={showTxMenu && isCheckedIn}
        transparent
        animationType="fade"
      >
        <TouchableOpacity
          style={styles.overlay}
          activeOpacity={1}
          onPress={() => setShowTxMenu(false)}
        >
          <View style={styles.menuContainer}>
            <TouchableOpacity
              style={styles.menuItem}
              onPress={() => handleTxOption("Order")}
            >
              <MaterialCommunityIcons
                name="cart-outline"
                size={20}
                color="#374151"
              />
              <Text style={styles.menuItemText}>Order</Text>
            </TouchableOpacity>
            <TouchableOpacity
              style={styles.menuItem}
              onPress={() => handleTxOption("Order Return")}
            >
              <MaterialCommunityIcons
                name="cart-arrow-up"
                size={20}
                color="#374151"
              />
              <Text style={styles.menuItemText}>Order Return</Text>
            </TouchableOpacity>
            <TouchableOpacity
              style={styles.menuItem}
              onPress={() => handleTxOption("Payment")}
            >
              <MaterialCommunityIcons
                name="cash-multiple"
                size={20}
                color="#374151"
              />
              <Text style={styles.menuItemText}>Payment</Text>
            </TouchableOpacity>
            <TouchableOpacity
              style={styles.menuItem}
              onPress={() => handleTxOption("Customer Profile")}
            >
              <MaterialCommunityIcons
                name="account-details"
                size={20}
                color="#374151"
              />
              <Text style={styles.menuItemText}>Customer Profile</Text>
            </TouchableOpacity>
            <TouchableOpacity
              style={styles.menuItem}
              onPress={() => handleTxOption("Location Tag")}
            >
              <MaterialCommunityIcons
                name="map-marker-radius"
                size={20}
                color="#374151"
              />
              <Text style={styles.menuItemText}>Location Tag</Text>
            </TouchableOpacity>
          </View>
        </TouchableOpacity>
      </Modal>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  safe: { flex: 1, backgroundColor: "#EDEDED" },
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

  scrollContent: { padding: 14, paddingBottom: 100 },

  card: {
    backgroundColor: "#fff",
    borderRadius: 16,
    padding: 16,
    marginBottom: 16,
    elevation: 4,
  },

  userCard: {
    backgroundColor: "#fff",
    borderRadius: 16,
    padding: 14,
    flexDirection: "row",
    alignItems: "center",
    marginBottom: 16,
    shadowColor: "#7367F0",
    shadowOpacity: 0.08,
    shadowRadius: 8,
    elevation: 4,
    borderLeftWidth: 4,
    borderLeftColor: "#7367F0",
  },
  userInfo: { flex: 1 },

  partyName: {
    fontSize: 15,
    fontWeight: "900",
    color: "#111827",
    marginBottom: 6,
  },
  row: { flexDirection: "row", alignItems: "center", gap: 6, marginBottom: 2 },
  partyDetail: {
    fontSize: 12,
    color: "#4b5563",
    fontWeight: "600",
    flexShrink: 1,
  },

  compactBtn: {
    width: 72,
    height: 72,
    borderRadius: 36,
    alignItems: "center",
    justifyContent: "center",
    elevation: 8,
    marginLeft: 12,
  },
  checkInBtn: { backgroundColor: "#10B981", shadowColor: "#10B981" }, // Green
  checkOutBtn: { backgroundColor: "#F59E0B", shadowColor: "#F59E0B" }, // Orange
  compactBtnText: {
    color: "#fff",
    fontSize: 10,
    fontWeight: "900",
    textAlign: "center",
    marginTop: 2,
    lineHeight: 13,
  },

  warningText: {
    color: "#ef4444",
    fontWeight: "bold",
    marginTop: 8,
    fontSize: 11,
  },
  infoText: { color: "#10B981", fontWeight: "800", marginTop: 8, fontSize: 11 },

  sectionTitle: {
    fontSize: 16,
    fontWeight: "800",
    color: "#111827",
    marginBottom: 12,
  },
  emptyState: { alignItems: "center", paddingVertical: 20 },
  emptyStateText: { color: "#8e8e8e", marginTop: 8, fontWeight: "600" },

  bottomBar: {
    position: "absolute",
    bottom: 0,
    left: 0,
    right: 0,
    backgroundColor: "#7367F0",
    flexDirection: "row",
    paddingBottom: 20,
    paddingTop: 12,
    elevation: 20,
    borderTopLeftRadius: 20,
    borderTopRightRadius: 20,
  },
  bottomBtn: {
    flex: 1,
    alignItems: "center",
    justifyContent: "center",
    gap: 4,
  },
  bottomBtnText: { color: "#fff", fontSize: 13, fontWeight: "800" },
  divider: {
    width: 1,
    backgroundColor: "rgba(255,255,255,0.3)",
    marginVertical: 4,
  },

  overlay: {
    flex: 1,
    backgroundColor: "rgba(0,0,0,0.4)",
    justifyContent: "flex-end",
    paddingBottom: 85,
  },
  menuContainer: {
    backgroundColor: "#fff",
    marginHorizontal: 16,
    borderRadius: 16,
    paddingVertical: 8,
    elevation: 10,
    shadowColor: "#000",
    shadowOpacity: 0.15,
    shadowRadius: 10,
  },
  menuItem: {
    flexDirection: "row",
    alignItems: "center",
    paddingVertical: 14,
    paddingHorizontal: 20,
    gap: 12,
  },
  menuItemText: {
    fontSize: 15,
    fontWeight: "700",
    color: "#374151",
  },
});
