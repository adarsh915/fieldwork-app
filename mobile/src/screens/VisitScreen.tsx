import { MaterialCommunityIcons, MaterialIcons } from "@expo/vector-icons";
import {
  useNavigation,
  useRoute,
  useFocusEffect,
} from "@react-navigation/native";
import type { RouteProp } from "@react-navigation/native";
import type { NativeStackNavigationProp } from "@react-navigation/native-stack";
import * as Location from "expo-location";
import * as ImagePicker from "expo-image-picker";
import { useCallback, useEffect, useRef, useState } from "react";
import {
  ActivityIndicator,
  Alert,
  FlatList,
  KeyboardAvoidingView,
  Modal,
  Platform,
  RefreshControl,
  ScrollView,
  StyleSheet,
  Text,
  TextInput,
  TouchableOpacity,
  View,
} from "react-native";
import { SafeAreaView, useSafeAreaInsets } from "react-native-safe-area-context";

import ScreenHeader from "../components/ScreenHeader";
import {
  checkIn,
  checkOut,
  getPartyVisitHistory,
  getPartyVisitStatus,
  updateVisitNotes,
  type Visit,
} from "../api/visit";
import { getOrders } from "../api/order";
import { useAlert } from "../context/AlertModalContext";
import type { AppStackParamList } from "../navigation/RootNavigator";

type Nav = NativeStackNavigationProp<AppStackParamList, "Visit">;
type RouteType = RouteProp<AppStackParamList, "Visit">;

const QUICK_TAGS = [
  "Catalog & Samples Shown",
  "Stock & Rate Inquiry",
  "Payment Follow-up",
  "Owner Busy / Rescheduled",
  "General Courtesy Visit",
];

export default function VisitScreen() {
  const navigation = useNavigation<Nav>();
  const route = useRoute<RouteType>();
  const insets = useSafeAreaInsets();
  const { partyId, partyName, partyMobile, partyAddress } = route.params;
  const { showSuccess, showError, showInfo } = useAlert();

  const [loading, setLoading] = useState(true);
  const [submitting, setSubmitting] = useState(false);
  const [savingNotes, setSavingNotes] = useState(false);
  const [notesSaved, setNotesSaved] = useState(false);
  const [activeVisit, setActiveVisit] = useState<Visit | null>(null);
  const [hasOtherVisit, setHasOtherVisit] = useState(false);
  const [showTxMenu, setShowTxMenu] = useState(false);
  const [showCheckOutModal, setShowCheckOutModal] = useState(false);
  const [consultationNotes, setConsultationNotes] = useState("");
  const [recentVisits, setRecentVisits] = useState<Visit[]>([]);
  const [orders, setOrders] = useState<any[]>([]);
  const [totalOrders, setTotalOrders] = useState(0);
  const [totalConsultations, setTotalConsultations] = useState(0);
  const [loadingOrders, setLoadingOrders] = useState(false);
  const [showOrdersModal, setShowOrdersModal] = useState(false);
  const [showConsultationsModal, setShowConsultationsModal] = useState(false);

  // Fetch visit status and initial lightweight order preview
  const loadStatus = useCallback(() => {
    setLoading(true);

    // 1. Visit Status (critical path for Check-in / Check-out button)
    getPartyVisitStatus(partyId)
      .then((statusRes) => {
        if (statusRes.success) {
          setActiveVisit(statusRes.active_visit);
          setHasOtherVisit(statusRes.has_other_active_visit);
          if (statusRes.active_visit) {
            setConsultationNotes(
              statusRes.active_visit.notes ||
                statusRes.active_visit.remarks ||
                ""
            );
          }
          if (statusRes.recent_visits) {
            setRecentVisits(statusRes.recent_visits);
          }
          if (statusRes.total_consultations !== undefined) {
            setTotalConsultations(statusRes.total_consultations);
          } else if (statusRes.recent_visits) {
            setTotalConsultations(statusRes.recent_visits.length);
          }
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

    // 2. Orders preview (top 5 latest only, lightweight pagination)
    setLoadingOrders(true);
    getOrders(partyId, { page: 1, per_page: 5 })
      .then((ordersRes) => {
        if (ordersRes && ordersRes.data) {
          setOrders(ordersRes.data);
          setTotalOrders(ordersRes.total ?? ordersRes.data.length);
        }
      })
      .catch((err) => {
        console.error("Failed to load orders preview:", err);
      })
      .finally(() => setLoadingOrders(false));
  }, [partyId, navigation]);

  useFocusEffect(
    useCallback(() => {
      loadStatus();
    }, [loadStatus]),
  );

  const handleSelectQuickTag = (tag: string) => {
    setConsultationNotes((prev) => {
      const trimmed = prev.trim();
      if (!trimmed) return tag;
      if (trimmed.includes(tag)) return prev;
      return `${trimmed}\n• ${tag}`;
    });
  };

  const handleSaveNotes = async () => {
    if (!activeVisit) return;
    setSavingNotes(true);
    try {
      await updateVisitNotes(activeVisit.id, consultationNotes.trim());
      setNotesSaved(true);
      setTimeout(() => setNotesSaved(false), 2500);
    } catch (error: any) {
      Alert.alert("Error", error.message || "Failed to save notes");
    } finally {
      setSavingNotes(false);
    }
  };

  const handleCheckIn = async () => {
    if (hasOtherVisit) {
      Alert.alert(
        "Notice",
        "You have another ongoing visit. Please check out of that first.",
      );
      return;
    }

    // Force party meet photo
    const { status: cameraStatus } = await ImagePicker.requestCameraPermissionsAsync();
    if (cameraStatus !== "granted") {
      Alert.alert("Permission needed", "Camera access is required to take a party meet photo.");
      return;
    }

    const imageResult = await ImagePicker.launchCameraAsync({
      mediaTypes: ImagePicker.MediaTypeOptions.Images,
      quality: 0.7,
      allowsEditing: false,
    });

    if (imageResult.canceled || imageResult.assets.length === 0) {
      return; // user cancelled camera
    }

    const checkInPhotoUri = imageResult.assets[0].uri;

    setSubmitting(true);
    try {
      const { status } = await Location.requestForegroundPermissionsAsync();
      if (status !== "granted") {
        Alert.alert("Permission needed", "Location access is required to check in.");
        setSubmitting(false);
        return;
      }
      const location = await Location.getCurrentPositionAsync({});
      
      const res = await checkIn({ 
        party_id: partyId,
        lat: location.coords.latitude,
        lng: location.coords.longitude,
        check_in_photo_uri: checkInPhotoUri,
        notes: consultationNotes.trim() || undefined,
      });
      if (res.success) {
        setActiveVisit(res.data);
      }
    } catch (error: any) {
      showError("Check-in Failed", error.message || "Failed to complete check-in.");
    } finally {
      setSubmitting(false);
    }
  };

  const handleConfirmCheckOut = async () => {
    if (!activeVisit) return;

    setSubmitting(true);
    try {
      let lat: number | undefined;
      let lng: number | undefined;
      try {
        const location = await Location.getCurrentPositionAsync({});
        lat = location.coords.latitude;
        lng = location.coords.longitude;
      } catch (e) {
        // ignore location error on checkout if it fails
      }

      const notesToSubmit =
        consultationNotes.trim() || "General Consultation / Visit";

      const res = await checkOut(activeVisit.id, {
        remarks: notesToSubmit,
        notes: notesToSubmit,
        lat,
        lng,
      });
      if (res.success) {
        setShowCheckOutModal(false);
        setActiveVisit(null);
        showSuccess(
          "Checked Out Successfully!",
          "Visit record and consultation notes have been saved.",
          () => navigation.goBack()
        );
      }
    } catch (error: any) {
      showError("Check-out Failed", error.message || "Failed to complete check-out.");
    } finally {
      setSubmitting(false);
    }
  };

  const handleTxOption = (option: string) => {
    setShowTxMenu(false);
    if (option === "Order" && activeVisit) {
      navigation.navigate("Order", {
        visitId: activeVisit.id,
        partyId,
        partyName,
        partyMobile,
        partyAddress,
      });
    } else if (option === "Payment") {
      navigation.navigate("PaymentCollection", {
        partyId,
        partyName,
        visitId: activeVisit?.id,
      });
    } else if (option === "Customer Profile") {
      navigation.navigate("CustomerProfile", { partyId });
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
    <View style={styles.safe}>
      {/* Seamless Purple Screen Header */}
      <ScreenHeader
        title="Visit details"
        onBack={() => navigation.goBack()}
        bgColor="#7367F0"
      />

      <ScrollView
        contentContainerStyle={[
          styles.scrollContent,
          { paddingBottom: Math.max(Math.min(insets.bottom, 16), 10) + 70 },
        ]}
        // Close menu if user scrolls
        onScrollBeginDrag={() => setShowTxMenu(false)}
        keyboardShouldPersistTaps="handled"
      >
        {/* Party Info Card with Check In / Check Out Button */}
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
            {isCheckedIn && (activeVisit?.check_in_at || activeVisit?.check_in_time) && (
              <Text style={styles.infoText}>
                Checked in:{" "}
                {new Date(
                  (activeVisit.check_in_at || activeVisit.check_in_time)!
                ).toLocaleTimeString([], {
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
            onPress={isCheckedIn ? () => setShowCheckOutModal(true) : handleCheckIn}
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

        {/* Visit Consultation Notes (Message Box during active visit) */}
        {isCheckedIn && (
          <View style={styles.consultationCard}>
            <View style={styles.consultationHeader}>
              <View style={styles.consultationTitleRow}>
                <MaterialCommunityIcons
                  name="notebook-edit-outline"
                  size={20}
                  color="#7367F0"
                />
                <Text style={styles.consultationTitle}>Consultation Notes</Text>
              </View>
              <TouchableOpacity
                style={[styles.saveNotesBtn, savingNotes && { opacity: 0.7 }]}
                onPress={handleSaveNotes}
                disabled={savingNotes}
              >
                {savingNotes ? (
                  <ActivityIndicator size="small" color="#7367F0" />
                ) : (
                  <>
                    <MaterialCommunityIcons
                      name={notesSaved ? "check-circle" : "content-save-outline"}
                      size={16}
                      color={notesSaved ? "#10B981" : "#7367F0"}
                    />
                    <Text
                      style={[
                        styles.saveNotesText,
                        notesSaved && { color: "#10B981" },
                      ]}
                    >
                      {notesSaved ? "Saved" : "Save"}
                    </Text>
                  </>
                )}
              </TouchableOpacity>
            </View>

            <Text style={styles.consultationHint}>
              Record discussion details or what you consulted with the party (especially if no order was placed):
            </Text>

            {/* Quick chips */}
            <ScrollView
              horizontal
              showsHorizontalScrollIndicator={false}
              style={styles.chipsScroll}
              contentContainerStyle={styles.chipsContainer}
            >
              {QUICK_TAGS.map((tag, idx) => (
                <TouchableOpacity
                  key={idx}
                  style={styles.chip}
                  onPress={() => handleSelectQuickTag(tag)}
                >
                  <Text style={styles.chipText}>{tag}</Text>
                </TouchableOpacity>
              ))}
            </ScrollView>

            <TextInput
              style={styles.messageBox}
              placeholder="What did you consult with the party? (e.g. Discussed catalog, pending balance follow-up, stock verification...)"
              placeholderTextColor="#9CA3AF"
              multiline
              numberOfLines={4}
              textAlignVertical="top"
              value={consultationNotes}
              onChangeText={setConsultationNotes}
            />
          </View>
        )}

        {/* Order History */}
        <View style={styles.card}>
          <View style={styles.sectionHeaderRow}>
            <View style={{ flexDirection: "row", alignItems: "center", gap: 8 }}>
              <MaterialCommunityIcons
                name="clipboard-text-clock-outline"
                size={20}
                color="#7367F0"
              />
              <Text style={styles.sectionTitle}>Order History</Text>
            </View>
            {totalOrders > 0 && (
              <View style={styles.countBadge}>
                <Text style={styles.countBadgeText}>{totalOrders}</Text>
              </View>
            )}
          </View>

          {loadingOrders && orders.length === 0 ? (
            <View style={{ paddingVertical: 18, alignItems: "center" }}>
              <ActivityIndicator size="small" color="#7367F0" />
            </View>
          ) : orders.length === 0 ? (
            <View style={styles.emptyState}>
              <MaterialCommunityIcons
                name="clipboard-text-outline"
                size={32}
                color="#ccc"
              />
              <Text style={styles.emptyStateText}>No recent orders</Text>
            </View>
          ) : (
            <>
              {orders.slice(0, 5).map((order, index) => (
                <TouchableOpacity
                  key={order.id || index}
                  style={styles.orderRow}
                  onPress={() => navigation.navigate("OrderDetails", { order })}
                >
                  <View style={styles.orderHeader}>
                    <Text style={styles.orderNo}>{order.order_no}</Text>
                    <Text
                      style={[
                        styles.orderStatus,
                        order.status === "completed" ? { color: "#10B981" } : {},
                      ]}
                    >
                      {(order.status || "pending").toUpperCase()}
                    </Text>
                  </View>
                  <View style={styles.orderBody}>
                    <Text style={styles.orderDate}>
                      {new Date(order.created_at).toLocaleDateString()}
                    </Text>
                    <Text style={styles.orderAmount}>
                      ₹ {Number(order.total_amount).toFixed(2)}
                    </Text>
                  </View>
                </TouchableOpacity>
              ))}

              {totalOrders > 5 && (
                <TouchableOpacity
                  style={styles.viewAllBtn}
                  activeOpacity={0.7}
                  onPress={() => setShowOrdersModal(true)}
                >
                  <Text style={styles.viewAllBtnText}>
                    View All Orders ({totalOrders})
                  </Text>
                  <MaterialIcons name="chevron-right" size={20} color="#7367F0" />
                </TouchableOpacity>
              )}
            </>
          )}
        </View>

        {/* Visit / Consultation History */}
        <View style={styles.card}>
          <View style={styles.sectionHeaderRow}>
            <View style={{ flexDirection: "row", alignItems: "center", gap: 8 }}>
              <MaterialCommunityIcons
                name="history"
                size={20}
                color="#7367F0"
              />
              <Text style={styles.sectionTitle}>Visit / Consultation History</Text>
            </View>
            {totalConsultations > 0 && (
              <View style={styles.countBadge}>
                <Text style={styles.countBadgeText}>{totalConsultations}</Text>
              </View>
            )}
          </View>

          {recentVisits.length === 0 ? (
            <View style={styles.emptyState}>
              <MaterialCommunityIcons name="history" size={32} color="#ccc" />
              <Text style={styles.emptyStateText}>No previous consultation notes</Text>
            </View>
          ) : (
            <>
              {recentVisits.slice(0, 5).map((item, index) => (
                <View key={item.id || index} style={styles.historyRow}>
                  <View style={styles.historyHeader}>
                    <Text style={styles.historyDate}>
                      {item.check_out_time || item.check_out_at || item.check_in_time || item.check_in_at
                        ? new Date(
                            (item.check_out_time ||
                              item.check_out_at ||
                              item.check_in_time ||
                              item.check_in_at)!
                          ).toLocaleDateString([], {
                            day: "numeric",
                            month: "short",
                            year: "numeric",
                          })
                        : "Recent Visit"}
                    </Text>
                    {item.user?.name && (
                      <Text style={styles.historyUser}>By {item.user.name}</Text>
                    )}
                  </View>
                  <Text style={styles.historyNotes}>
                    {item.notes || item.remarks}
                  </Text>
                </View>
              ))}

              {totalConsultations > 5 && (
                <TouchableOpacity
                  style={styles.viewAllBtn}
                  activeOpacity={0.7}
                  onPress={() => setShowConsultationsModal(true)}
                >
                  <Text style={styles.viewAllBtnText}>
                    View Full Consultation History ({totalConsultations})
                  </Text>
                  <MaterialIcons name="chevron-right" size={20} color="#7367F0" />
                </TouchableOpacity>
              )}
            </>
          )}
        </View>
      </ScrollView>

      {/* Bottom Action Buttons */}
      <View
        style={[
          styles.bottomBar,
          {
            paddingTop: 10,
            paddingBottom: Math.max(Math.min(insets.bottom, 16), 10),
          },
        ]}
      >
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
          style={[
            styles.overlay,
            { paddingBottom: Math.max(Math.min(insets.bottom, 16), 10) + 64 },
          ]}
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

      {/* Check-Out Confirmation & Consultation Message Box Modal */}
      <Modal
        visible={showCheckOutModal}
        transparent
        animationType="fade"
        onRequestClose={() => {
          if (!submitting) setShowCheckOutModal(false);
        }}
      >
        <KeyboardAvoidingView
          behavior={Platform.OS === "ios" ? "padding" : "height"}
          style={styles.modalOverlay}
        >
          <View style={styles.modalCard}>
            <View style={styles.modalHeader}>
              <View style={styles.modalHeaderIconWrap}>
                <MaterialCommunityIcons
                  name="logout-variant"
                  size={22}
                  color="#F59E0B"
                />
              </View>
              <View style={{ flex: 1 }}>
                <Text style={styles.modalTitle}>Check Out from Visit</Text>
                <Text style={styles.modalSubtitle} numberOfLines={1}>
                  {partyName}
                </Text>
              </View>
              <TouchableOpacity
                onPress={() => setShowCheckOutModal(false)}
                disabled={submitting}
                style={styles.modalCloseBtn}
              >
                <MaterialIcons name="close" size={22} color="#6B7280" />
              </TouchableOpacity>
            </View>

            <Text style={styles.modalInstruction}>
              Record consultation notes or what you discussed with the party before completing check-out:
            </Text>

            {/* Quick Chips in modal */}
            <ScrollView
              horizontal
              showsHorizontalScrollIndicator={false}
              style={styles.chipsScroll}
              contentContainerStyle={styles.chipsContainer}
            >
              {QUICK_TAGS.map((tag, idx) => (
                <TouchableOpacity
                  key={idx}
                  style={styles.chip}
                  onPress={() => handleSelectQuickTag(tag)}
                >
                  <Text style={styles.chipText}>{tag}</Text>
                </TouchableOpacity>
              ))}
            </ScrollView>

            <TextInput
              style={styles.modalInput}
              placeholder="What was discussed / consulted? (e.g. Catalog shown, payment follow-up, stock checked...)"
              placeholderTextColor="#9CA3AF"
              multiline
              numberOfLines={4}
              textAlignVertical="top"
              value={consultationNotes}
              onChangeText={setConsultationNotes}
            />

            <View style={styles.modalBtnRow}>
              <TouchableOpacity
                style={styles.modalCancelBtn}
                onPress={() => setShowCheckOutModal(false)}
                disabled={submitting}
              >
                <Text style={styles.modalCancelText}>Cancel</Text>
              </TouchableOpacity>

              <TouchableOpacity
                style={[
                  styles.modalConfirmBtn,
                  submitting && { opacity: 0.7 },
                ]}
                onPress={handleConfirmCheckOut}
                disabled={submitting}
              >
                {submitting ? (
                  <ActivityIndicator size="small" color="#fff" />
                ) : (
                  <>
                    <MaterialCommunityIcons
                      name="check-circle-outline"
                      size={18}
                      color="#fff"
                    />
                    <Text style={styles.modalConfirmText}>
                      Confirm Check Out
                    </Text>
                  </>
                )}
              </TouchableOpacity>
            </View>
          </View>
        </KeyboardAvoidingView>
      </Modal>

      <AllOrdersModal
        visible={showOrdersModal}
        partyId={partyId}
        partyName={partyName}
        onClose={() => setShowOrdersModal(false)}
        onSelectOrder={(order) => {
          setShowOrdersModal(false);
          navigation.navigate("OrderDetails", { order });
        }}
      />

      <AllConsultationsModal
        visible={showConsultationsModal}
        partyId={partyId}
        partyName={partyName}
        onClose={() => setShowConsultationsModal(false)}
      />
    </View>
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

  orderRow: {
    paddingVertical: 12,
    borderBottomWidth: 1,
    borderBottomColor: "#f3f4f6",
  },
  orderHeader: {
    flexDirection: "row",
    justifyContent: "space-between",
    alignItems: "center",
    marginBottom: 4,
  },
  orderNo: {
    fontSize: 14,
    fontWeight: "700",
    color: "#374151",
  },
  orderStatus: {
    fontSize: 12,
    fontWeight: "800",
    color: "#F59E0B",
  },
  orderBody: {
    flexDirection: "row",
    justifyContent: "space-between",
    alignItems: "center",
  },
  orderDate: {
    fontSize: 12,
    color: "#6b7280",
  },
  orderAmount: {
    fontSize: 14,
    fontWeight: "800",
    color: "#111827",
  },

  bottomBar: {
    position: "absolute",
    bottom: 0,
    left: 0,
    right: 0,
    backgroundColor: "#7367F0",
    flexDirection: "row",
    alignItems: "center",
    paddingTop: 10,
    paddingBottom: 10,
    elevation: 20,
    borderTopLeftRadius: 20,
    borderTopRightRadius: 20,
  },
  bottomBtn: {
    flex: 1,
    alignItems: "center",
    justifyContent: "center",
    paddingVertical: 2,
    gap: 4,
  },
  bottomBtnText: { color: "#fff", fontSize: 13, fontWeight: "800" },
  divider: {
    width: 1,
    backgroundColor: "rgba(255,255,255,0.3)",
    height: 32,
  },

  overlay: {
    flex: 1,
    backgroundColor: "rgba(0,0,0,0.4)",
    justifyContent: "flex-end",
    paddingBottom: 72,
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

  consultationCard: {
    backgroundColor: "#fff",
    borderRadius: 16,
    padding: 16,
    marginBottom: 16,
    elevation: 4,
    shadowColor: "#7367F0",
    shadowOpacity: 0.08,
    shadowRadius: 8,
    borderLeftWidth: 4,
    borderLeftColor: "#7367F0",
  },
  consultationHeader: {
    flexDirection: "row",
    justifyContent: "space-between",
    alignItems: "center",
    marginBottom: 6,
  },
  consultationTitleRow: {
    flexDirection: "row",
    alignItems: "center",
    gap: 6,
  },
  consultationTitle: {
    fontSize: 15,
    fontWeight: "800",
    color: "#111827",
  },
  consultationHint: {
    fontSize: 12,
    color: "#6B7280",
    marginBottom: 10,
    lineHeight: 16,
  },
  saveNotesBtn: {
    flexDirection: "row",
    alignItems: "center",
    gap: 4,
    backgroundColor: "#F3F0FF",
    paddingHorizontal: 10,
    paddingVertical: 5,
    borderRadius: 8,
  },
  saveNotesText: {
    fontSize: 12,
    fontWeight: "700",
    color: "#7367F0",
  },
  chipsScroll: {
    marginBottom: 10,
    maxHeight: 36,
  },
  chipsContainer: {
    gap: 8,
    paddingVertical: 2,
  },
  chip: {
    backgroundColor: "#EEF2FF",
    borderWidth: 1,
    borderColor: "#C7D2FE",
    paddingHorizontal: 10,
    paddingVertical: 5,
    borderRadius: 20,
  },
  chipText: {
    fontSize: 11,
    fontWeight: "600",
    color: "#4338CA",
  },
  messageBox: {
    backgroundColor: "#F9FAFB",
    borderWidth: 1,
    borderColor: "#E5E7EB",
    borderRadius: 12,
    padding: 12,
    fontSize: 13,
    color: "#1F2937",
    minHeight: 85,
    textAlignVertical: "top",
  },

  modalOverlay: {
    flex: 1,
    backgroundColor: "rgba(0,0,0,0.5)",
    justifyContent: "center",
    padding: 16,
  },
  modalCard: {
    backgroundColor: "#fff",
    borderRadius: 20,
    padding: 20,
    elevation: 12,
    shadowColor: "#000",
    shadowOpacity: 0.2,
    shadowRadius: 12,
  },
  modalHeader: {
    flexDirection: "row",
    alignItems: "center",
    gap: 10,
    marginBottom: 12,
  },
  modalHeaderIconWrap: {
    width: 40,
    height: 40,
    borderRadius: 20,
    backgroundColor: "#FEF3C7",
    alignItems: "center",
    justifyContent: "center",
  },
  modalTitle: {
    fontSize: 17,
    fontWeight: "800",
    color: "#111827",
  },
  modalSubtitle: {
    fontSize: 13,
    color: "#6B7280",
    marginTop: 2,
  },
  modalCloseBtn: {
    padding: 4,
  },
  modalInstruction: {
    fontSize: 13,
    color: "#4B5563",
    marginBottom: 12,
    lineHeight: 18,
  },
  modalInput: {
    backgroundColor: "#F9FAFB",
    borderWidth: 1,
    borderColor: "#D1D5DB",
    borderRadius: 12,
    padding: 12,
    fontSize: 14,
    color: "#1F2937",
    minHeight: 110,
    textAlignVertical: "top",
    marginBottom: 16,
  },
  modalBtnRow: {
    flexDirection: "row",
    gap: 10,
    justifyContent: "flex-end",
  },
  modalCancelBtn: {
    paddingVertical: 12,
    paddingHorizontal: 16,
    borderRadius: 10,
    backgroundColor: "#F3F4F6",
    alignItems: "center",
    justifyContent: "center",
  },
  modalCancelText: {
    fontSize: 14,
    fontWeight: "700",
    color: "#4B5563",
  },
  modalConfirmBtn: {
    flex: 1,
    flexDirection: "row",
    alignItems: "center",
    justifyContent: "center",
    gap: 6,
    paddingVertical: 12,
    paddingHorizontal: 16,
    borderRadius: 10,
    backgroundColor: "#F59E0B",
  },
  modalConfirmText: {
    fontSize: 14,
    fontWeight: "800",
    color: "#fff",
  },

  historyRow: {
    paddingVertical: 10,
    borderBottomWidth: 1,
    borderBottomColor: "#F3F4F6",
  },
  historyHeader: {
    flexDirection: "row",
    justifyContent: "space-between",
    alignItems: "center",
    marginBottom: 4,
  },
  historyDate: {
    fontSize: 12,
    fontWeight: "700",
    color: "#4B5563",
  },
  historyUser: {
    fontSize: 11,
    fontWeight: "600",
    color: "#7367F0",
  },
  historyNotes: {
    fontSize: 13,
    color: "#1F2937",
    lineHeight: 18,
  },

  sectionHeaderRow: {
    flexDirection: "row",
    justifyContent: "space-between",
    alignItems: "center",
    marginBottom: 12,
  },
  countBadge: {
    backgroundColor: "#EEF2FF",
    paddingHorizontal: 8,
    paddingVertical: 2,
    borderRadius: 12,
    borderWidth: 1,
    borderColor: "#C7D2FE",
  },
  countBadgeText: {
    fontSize: 11,
    fontWeight: "800",
    color: "#4338CA",
  },
  viewAllBtn: {
    flexDirection: "row",
    alignItems: "center",
    justifyContent: "center",
    paddingVertical: 10,
    marginTop: 8,
    borderTopWidth: 1,
    borderTopColor: "#F3F4F6",
    gap: 4,
  },
  viewAllBtnText: {
    fontSize: 13,
    fontWeight: "700",
    color: "#7367F0",
  },
  fullModalContainer: {
    flex: 1,
    backgroundColor: "#F8FAFC",
  },
  fullModalHeader: {
    flexDirection: "row",
    alignItems: "center",
    justifyContent: "space-between",
    paddingHorizontal: 16,
    paddingVertical: 14,
    backgroundColor: "#FFFFFF",
    borderBottomWidth: 1,
    borderBottomColor: "#E2E8F0",
  },
  fullModalTitle: {
    fontSize: 17,
    fontWeight: "800",
    color: "#0F172A",
  },
  fullModalSubtitle: {
    fontSize: 12,
    fontWeight: "600",
    color: "#64748B",
    marginTop: 2,
  },
  fullModalCloseBtn: {
    width: 36,
    height: 36,
    borderRadius: 18,
    backgroundColor: "#F1F5F9",
    alignItems: "center",
    justifyContent: "center",
  },
  modalSearchWrap: {
    flexDirection: "row",
    alignItems: "center",
    backgroundColor: "#FFFFFF",
    marginHorizontal: 14,
    marginTop: 12,
    paddingHorizontal: 12,
    height: 44,
    borderRadius: 12,
    borderWidth: 1,
    borderColor: "#E2E8F0",
    gap: 8,
  },
  modalSearchInput: {
    flex: 1,
    fontSize: 14,
    color: "#1E293B",
    fontWeight: "600",
  },
  modalLoadingWrap: {
    flex: 1,
    alignItems: "center",
    justifyContent: "center",
    paddingTop: 60,
  },
  modalLoadingText: {
    marginTop: 12,
    fontSize: 13,
    color: "#64748B",
    fontWeight: "600",
  },
});

function AllOrdersModal({
  visible,
  partyId,
  partyName,
  onClose,
  onSelectOrder,
}: {
  visible: boolean;
  partyId: number | string;
  partyName: string;
  onClose: () => void;
  onSelectOrder: (order: any) => void;
}) {
  const [orders, setOrders] = useState<any[]>([]);
  const [page, setPage] = useState(1);
  const [hasMore, setHasMore] = useState(true);
  const [loading, setLoading] = useState(true);
  const [loadingMore, setLoadingMore] = useState(false);
  const [refreshing, setRefreshing] = useState(false);
  const [search, setSearch] = useState("");
  const reqIdRef = useRef(0);

  const fetchOrders = useCallback(
    async (pageToLoad: number, searchQuery: string, isRefresh = false) => {
      const curReqId = ++reqIdRef.current;
      if (pageToLoad === 1 && !isRefresh) setLoading(true);
      if (pageToLoad > 1) setLoadingMore(true);

      try {
        const res = await getOrders(partyId, {
          page: pageToLoad,
          per_page: 15,
          search: searchQuery.trim(),
        });
        if (curReqId !== reqIdRef.current) return;

        if (res && res.data) {
          if (pageToLoad === 1) {
            setOrders(res.data);
          } else {
            setOrders((prev) => [...prev, ...res.data]);
          }
          setPage(pageToLoad);
          setHasMore(res.has_more ?? (res.data.length >= 15));
        }
      } catch (e) {
        console.error(e);
      } finally {
        if (curReqId === reqIdRef.current) {
          setLoading(false);
          setLoadingMore(false);
          setRefreshing(false);
        }
      }
    },
    [partyId]
  );

  useEffect(() => {
    if (!visible) return;
    const timer = setTimeout(() => {
      fetchOrders(1, search);
    }, search ? 350 : 0);
    return () => clearTimeout(timer);
  }, [visible, search, fetchOrders]);

  const handleRefresh = () => {
    setRefreshing(true);
    fetchOrders(1, search, true);
  };

  const handleLoadMore = () => {
    if (!loading && !loadingMore && hasMore) {
      fetchOrders(page + 1, search);
    }
  };

  return (
    <Modal visible={visible} animationType="slide" onRequestClose={onClose}>
      <SafeAreaView style={styles.fullModalContainer}>
        {/* Modal Header */}
        <View style={styles.fullModalHeader}>
          <View style={{ flex: 1 }}>
            <Text style={styles.fullModalTitle}>Order History</Text>
            <Text style={styles.fullModalSubtitle} numberOfLines={1}>
              {partyName}
            </Text>
          </View>
          <TouchableOpacity
            style={styles.fullModalCloseBtn}
            onPress={onClose}
            hitSlop={{ top: 8, bottom: 8, left: 8, right: 8 }}
          >
            <MaterialIcons name="close" size={24} color="#1F2937" />
          </TouchableOpacity>
        </View>

        {/* Search Bar */}
        <View style={styles.modalSearchWrap}>
          <MaterialIcons name="search" size={20} color="#7367F0" />
          <TextInput
            style={styles.modalSearchInput}
            placeholder="Search by order number..."
            placeholderTextColor="#9CA3AF"
            value={search}
            onChangeText={setSearch}
            autoCapitalize="none"
          />
          {search ? (
            <TouchableOpacity onPress={() => setSearch("")}>
              <MaterialIcons name="close" size={18} color="#9CA3AF" />
            </TouchableOpacity>
          ) : null}
        </View>

        {/* Orders List */}
        {loading ? (
          <View style={styles.modalLoadingWrap}>
            <ActivityIndicator size="large" color="#7367F0" />
            <Text style={styles.modalLoadingText}>Loading orders...</Text>
          </View>
        ) : (
          <FlatList
            data={orders}
            keyExtractor={(item, idx) => String(item.id || idx)}
            contentContainerStyle={{ padding: 14, paddingBottom: 40 }}
            keyboardShouldPersistTaps="handled"
            refreshControl={
              <RefreshControl
                refreshing={refreshing}
                onRefresh={handleRefresh}
                colors={["#7367F0"]}
              />
            }
            onEndReached={handleLoadMore}
            onEndReachedThreshold={0.4}
            renderItem={({ item: order }) => (
              <TouchableOpacity
                style={styles.orderRow}
                activeOpacity={0.7}
                onPress={() => onSelectOrder(order)}
              >
                <View style={styles.orderHeader}>
                  <Text style={styles.orderNo}>{order.order_no}</Text>
                  <Text
                    style={[
                      styles.orderStatus,
                      order.status === "completed" ? { color: "#10B981" } : {},
                    ]}
                  >
                    {(order.status || "pending").toUpperCase()}
                  </Text>
                </View>
                <View style={styles.orderBody}>
                  <Text style={styles.orderDate}>
                    {new Date(order.created_at).toLocaleDateString()}
                  </Text>
                  <Text style={styles.orderAmount}>
                    ₹ {Number(order.total_amount).toFixed(2)}
                  </Text>
                </View>
              </TouchableOpacity>
            )}
            ListEmptyComponent={
              <View style={styles.emptyState}>
                <MaterialCommunityIcons
                  name="clipboard-text-outline"
                  size={42}
                  color="#CBD5E1"
                />
                <Text style={styles.emptyStateText}>
                  {search ? `No orders found for "${search}"` : "No orders found"}
                </Text>
              </View>
            }
            ListFooterComponent={
              loadingMore ? (
                <View style={{ paddingVertical: 14, alignItems: "center" }}>
                  <ActivityIndicator size="small" color="#7367F0" />
                </View>
              ) : null
            }
          />
        )}
      </SafeAreaView>
    </Modal>
  );
}

function AllConsultationsModal({
  visible,
  partyId,
  partyName,
  onClose,
}: {
  visible: boolean;
  partyId: number | string;
  partyName: string;
  onClose: () => void;
}) {
  const [visits, setVisits] = useState<Visit[]>([]);
  const [page, setPage] = useState(1);
  const [hasMore, setHasMore] = useState(true);
  const [loading, setLoading] = useState(true);
  const [loadingMore, setLoadingMore] = useState(false);
  const [refreshing, setRefreshing] = useState(false);
  const [search, setSearch] = useState("");
  const reqIdRef = useRef(0);

  const fetchVisits = useCallback(
    async (pageToLoad: number, searchQuery: string, isRefresh = false) => {
      const curReqId = ++reqIdRef.current;
      if (pageToLoad === 1 && !isRefresh) setLoading(true);
      if (pageToLoad > 1) setLoadingMore(true);

      try {
        const res = await getPartyVisitHistory(partyId, {
          page: pageToLoad,
          per_page: 15,
          search: searchQuery.trim(),
        });
        if (curReqId !== reqIdRef.current) return;

        if (res && res.data) {
          if (pageToLoad === 1) {
            setVisits(res.data);
          } else {
            setVisits((prev) => [...prev, ...res.data]);
          }
          setPage(pageToLoad);
          setHasMore(res.has_more ?? (res.data.length >= 15));
        }
      } catch (e) {
        console.error(e);
      } finally {
        if (curReqId === reqIdRef.current) {
          setLoading(false);
          setLoadingMore(false);
          setRefreshing(false);
        }
      }
    },
    [partyId]
  );

  useEffect(() => {
    if (!visible) return;
    const timer = setTimeout(() => {
      fetchVisits(1, search);
    }, search ? 350 : 0);
    return () => clearTimeout(timer);
  }, [visible, search, fetchVisits]);

  const handleRefresh = () => {
    setRefreshing(true);
    fetchVisits(1, search, true);
  };

  const handleLoadMore = () => {
    if (!loading && !loadingMore && hasMore) {
      fetchVisits(page + 1, search);
    }
  };

  return (
    <Modal visible={visible} animationType="slide" onRequestClose={onClose}>
      <SafeAreaView style={styles.fullModalContainer}>
        {/* Modal Header */}
        <View style={styles.fullModalHeader}>
          <View style={{ flex: 1 }}>
            <Text style={styles.fullModalTitle}>Consultation History</Text>
            <Text style={styles.fullModalSubtitle} numberOfLines={1}>
              {partyName}
            </Text>
          </View>
          <TouchableOpacity
            style={styles.fullModalCloseBtn}
            onPress={onClose}
            hitSlop={{ top: 8, bottom: 8, left: 8, right: 8 }}
          >
            <MaterialIcons name="close" size={24} color="#1F2937" />
          </TouchableOpacity>
        </View>

        {/* Search Bar */}
        <View style={styles.modalSearchWrap}>
          <MaterialIcons name="search" size={20} color="#7367F0" />
          <TextInput
            style={styles.modalSearchInput}
            placeholder="Search consultation notes..."
            placeholderTextColor="#9CA3AF"
            value={search}
            onChangeText={setSearch}
            autoCapitalize="none"
          />
          {search ? (
            <TouchableOpacity onPress={() => setSearch("")}>
              <MaterialIcons name="close" size={18} color="#9CA3AF" />
            </TouchableOpacity>
          ) : null}
        </View>

        {/* Consultations List */}
        {loading ? (
          <View style={styles.modalLoadingWrap}>
            <ActivityIndicator size="large" color="#7367F0" />
            <Text style={styles.modalLoadingText}>Loading consultations...</Text>
          </View>
        ) : (
          <FlatList
            data={visits}
            keyExtractor={(item, idx) => String(item.id || idx)}
            contentContainerStyle={{ padding: 14, paddingBottom: 40 }}
            keyboardShouldPersistTaps="handled"
            refreshControl={
              <RefreshControl
                refreshing={refreshing}
                onRefresh={handleRefresh}
                colors={["#7367F0"]}
              />
            }
            onEndReached={handleLoadMore}
            onEndReachedThreshold={0.4}
            renderItem={({ item }) => (
              <View style={styles.historyRow}>
                <View style={styles.historyHeader}>
                  <Text style={styles.historyDate}>
                    {item.check_out_time || item.check_out_at || item.check_in_time || item.check_in_at
                      ? new Date(
                          (item.check_out_time ||
                            item.check_out_at ||
                            item.check_in_time ||
                            item.check_in_at)!
                        ).toLocaleDateString([], {
                          day: "numeric",
                          month: "short",
                          year: "numeric",
                        })
                      : "Recent Visit"}
                  </Text>
                  {item.user?.name && (
                    <Text style={styles.historyUser}>By {item.user.name}</Text>
                  )}
                </View>
                <Text style={styles.historyNotes}>
                  {item.notes || item.remarks}
                </Text>
              </View>
            )}
            ListEmptyComponent={
              <View style={styles.emptyState}>
                <MaterialCommunityIcons
                  name="history"
                  size={42}
                  color="#CBD5E1"
                />
                <Text style={styles.emptyStateText}>
                  {search ? `No notes matching "${search}"` : "No consultation notes found"}
                </Text>
              </View>
            }
            ListFooterComponent={
              loadingMore ? (
                <View style={{ paddingVertical: 14, alignItems: "center" }}>
                  <ActivityIndicator size="small" color="#7367F0" />
                </View>
              ) : null
            }
          />
        )}
      </SafeAreaView>
    </Modal>
  );
}
