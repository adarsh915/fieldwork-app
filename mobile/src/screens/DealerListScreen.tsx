import { MaterialCommunityIcons, MaterialIcons } from "@expo/vector-icons";
import { useNavigation, useFocusEffect } from "@react-navigation/native";
import type { NativeStackNavigationProp } from "@react-navigation/native-stack";
import { useState, useEffect, useCallback, useRef } from "react";
import {
  ActivityIndicator,
  Alert,
  Dimensions,
  FlatList,
  Modal,
  RefreshControl,
  ScrollView,
  StyleSheet,
  Text,
  TextInput,
  TouchableOpacity,
  View,
} from "react-native";
import { SafeAreaView } from "react-native-safe-area-context";

import ScreenHeader from "../components/ScreenHeader";
import { getMasterPartiesPaginated, type Party } from "../api/master";
import type { RootStackParamList } from "../navigation/RootNavigator";

type Nav = NativeStackNavigationProp<RootStackParamList, "DealerList">;

const ACTIONS: {
  label: string;
  icon: keyof typeof MaterialCommunityIcons.glyphMap;
}[] = [
  { label: "Phone Order", icon: "phone-plus" },
  { label: "Visit", icon: "map-marker-outline" },
  { label: "Party History", icon: "history" },
  /* Hidden per request (hidden, not removed):
  { label: "Order", icon: "cart-outline" },
  { label: "Payment", icon: "currency-inr" },
  { label: "Ledger", icon: "clipboard-text-outline" },
  { label: "Find Location", icon: "crosshairs-gps" },
  { label: "Contact Dealer", icon: "account-box-outline" },
  { label: "Add Reminder", icon: "alarm" },
  { label: "Bill", icon: "receipt" },
  */
];

export default function DealerListScreen() {
  const navigation = useNavigation<Nav>();
  const [query, setQuery] = useState("");
  const queryRef = useRef(query);
  queryRef.current = query;

  const [menuDealer, setMenuDealer] = useState<Party | null>(null);
  const [menuPosition, setMenuPosition] = useState<{ x: number; y: number }>({ x: 0, y: 0 });

  const [parties, setParties] = useState<Party[]>([]);
  const [page, setPage] = useState(1);
  const [hasMore, setHasMore] = useState(true);
  const [totalDealers, setTotalDealers] = useState(0);
  const [completedVisits, setCompletedVisits] = useState(0);

  const [loading, setLoading] = useState(true);
  const [loadingMore, setLoadingMore] = useState(false);
  const [refreshing, setRefreshing] = useState(false);
  const [searching, setSearching] = useState(false);

  // Track latest request to avoid race conditions when typing quickly
  const requestIdRef = useRef(0);
  const isInitialMount = useRef(true);

  const loadParties = useCallback(async (pageToLoad: number, searchQuery: string, isRefresh = false) => {
    const currentRequestId = ++requestIdRef.current;

    if (pageToLoad === 1) {
      if (!isRefresh) {
        if (searchQuery.trim().length > 0) {
          setSearching(true);
        } else {
          setLoading(true);
        }
      }
    } else {
      setLoadingMore(true);
    }

    try {
      const res = await getMasterPartiesPaginated({
        page: pageToLoad,
        per_page: 20,
        search: searchQuery.trim(),
      });

      if (currentRequestId !== requestIdRef.current) return;

      if (res.success) {
        if (pageToLoad === 1) {
          setParties(res.data);
          if (typeof res.completed_visits_count === "number") {
            setCompletedVisits(res.completed_visits_count);
          }
        } else {
          setParties((prev) => {
            const existing = new Set(prev.map((p) => p.id));
            const fresh = res.data.filter((p) => !existing.has(p.id));
            return [...prev, ...fresh];
          });
        }
        setPage(res.current_page);
        setHasMore(res.has_more);
        setTotalDealers(res.total);
      }
    } catch (err: any) {
      console.error(err);
      if (pageToLoad === 1 && !isRefresh) {
        Alert.alert("Error", "Failed to load parties.");
      }
    } finally {
      if (currentRequestId === requestIdRef.current) {
        setLoading(false);
        setLoadingMore(false);
        setRefreshing(false);
        setSearching(false);
      }
    }
  }, []);

  // Initial load & debounced search when user types in search box
  useEffect(() => {
    if (isInitialMount.current) {
      isInitialMount.current = false;
      loadParties(1, "");
      return;
    }

    const timer = setTimeout(() => {
      loadParties(1, query);
    }, 350);

    return () => clearTimeout(timer);
  }, [query, loadParties]);

  // Refresh page 1 when screen is refocused (e.g. returning after adding a party)
  useFocusEffect(
    useCallback(() => {
      if (isInitialMount.current) return;
      loadParties(1, queryRef.current, true);
    }, [loadParties])
  );

  const handleRefresh = () => {
    setRefreshing(true);
    loadParties(1, query, true);
  };

  const handleClearQuery = () => {
    setQuery("");
    loadParties(1, "", false);
  };

  const handleLoadMore = () => {
    if (!loading && !loadingMore && !searching && hasMore) {
      loadParties(page + 1, query);
    }
  };

  const renderDealerCard = useCallback(({ item: dealer }: { item: Party }) => (
    <View style={styles.dealerCard}>
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
              activeOpacity={0.7}
              hitSlop={{ top: 8, bottom: 8, left: 8, right: 8 }}
              onPress={(e) => {
                const pageY = e.nativeEvent?.pageY || 120;
                const pageX = e.nativeEvent?.pageX || 300;
                setMenuPosition({ x: pageX, y: pageY });
                setMenuDealer(dealer);
              }}
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

        {/* Hidden: Bal, Whatsapp icon, and Credit Limit per request (hidden, not removed)
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
        */}
      </View>
    </View>
  ), []);

  const renderHeader = useCallback(
    () => (
      <View style={styles.totalsCard}>
        <View style={styles.totalCol}>
          <Text style={[styles.totalValue, { color: "#f59e0b" }]}>
            {totalDealers}
          </Text>
          <Text style={[styles.totalLabel, { color: "#f59e0b" }]}>
            Total Dealers
          </Text>
        </View>
        <View style={styles.totalDivider} />
        <View style={styles.totalCol}>
          <Text style={[styles.totalValue, { color: "#10B981" }]}>
            {completedVisits}
          </Text>
          <Text style={[styles.totalLabel, { color: "#10B981" }]}>
            Completed Visits
          </Text>
        </View>

        {/* Hidden: Total Credit and Total Debt per request (hidden, not removed)
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
        */}

        <View style={styles.totalProgress}>
          <View
            style={[styles.progressSeg, { backgroundColor: "#f59e0b" }]}
          />
          <View
            style={[styles.progressSeg, { backgroundColor: "#10B981" }]}
          />
          {/*
          <View
            style={[styles.progressSeg, { backgroundColor: "#7367F0" }]}
          />
          <View
            style={[styles.progressSeg, { backgroundColor: "#ef4444" }]}
          />
          */}
        </View>
      </View>
    ),
    [totalDealers, completedVisits]
  );

  const renderFooter = () => {
    if (!loadingMore) return null;
    return (
      <View style={styles.footerLoader}>
        <ActivityIndicator size="small" color="#7367F0" />
        <Text style={styles.footerLoaderText}>Loading more dealers...</Text>
      </View>
    );
  };

  const renderEmpty = () => {
    if (loading) {
      return (
        <View style={styles.centerLoading}>
          <ActivityIndicator size="large" color="#7367F0" />
          <Text style={styles.loadingText}>Loading dealers...</Text>
        </View>
      );
    }
    return (
      <Text style={styles.emptyText}>
        {query.trim()
          ? `No dealers found matching "${query.trim()}".`
          : "No parties found."}
      </Text>
    );
  };

  return (
    <View style={{ flex: 1, backgroundColor: "#F4F4F8" }}>
      <ScreenHeader
        title="Dealer List"
        onBack={() => navigation.goBack()}
      />

      <View style={styles.topSearchWrapper}>
        <View style={styles.searchWrap}>
          <TextInput
            style={styles.searchInput}
            placeholder="Search dealers by name, mobile..."
            placeholderTextColor="#8E8E8E"
            value={query}
            onChangeText={setQuery}
            returnKeyType="search"
            autoCapitalize="none"
            autoCorrect={false}
          />
          {searching ? (
            <ActivityIndicator size="small" color="#7367F0" />
          ) : query ? (
            <TouchableOpacity
              onPress={handleClearQuery}
              hitSlop={{ top: 8, bottom: 8, left: 8, right: 8 }}
            >
              <MaterialIcons name="close" size={20} color="#9CA3AF" />
            </TouchableOpacity>
          ) : (
            <MaterialIcons name="search" size={22} color="#7367F0" />
          )}
        </View>
      </View>

      <FlatList
        data={parties}
        keyExtractor={(item) => String(item.id)}
        renderItem={renderDealerCard}
        contentContainerStyle={styles.scrollContent}
        keyboardShouldPersistTaps="handled"
        ListHeaderComponent={renderHeader}
        ListEmptyComponent={renderEmpty}
        ListFooterComponent={renderFooter}
        onEndReached={handleLoadMore}
        onEndReachedThreshold={0.5}
        refreshControl={
          <RefreshControl
            refreshing={refreshing}
            onRefresh={handleRefresh}
            colors={["#7367F0"]}
            tintColor="#7367F0"
          />
        }
        initialNumToRender={10}
        maxToRenderPerBatch={10}
        windowSize={5}
        removeClippedSubviews={true}
      />

      <TouchableOpacity
        style={styles.fab}
        activeOpacity={0.9}
        onPress={() => navigation.navigate("AddParty")}
      >
        <MaterialIcons name="add" size={28} color="#FFFFFF" />
      </TouchableOpacity>

      <DealerMenuModal
        visible={!!menuDealer}
        dealer={menuDealer}
        position={menuPosition}
        onDismiss={() => setMenuDealer(null)}
        onAction={(action, dealer) => {
          if (action === "Phone Order") {
            navigation.navigate("Order", {
              partyId: dealer.id,
              partyName: dealer.name,
              partyMobile: dealer.mobile,
              partyAddress: dealer.address,
              partyCity: dealer.city,
              visitId: null,
              isDirectPhoneOrder: true,
            });
          } else if (action === "Visit") {
            navigation.navigate("Visit", {
              partyId: dealer.id,
              partyName: dealer.name,
              partyMobile: dealer.mobile,
              partyAddress: dealer.address,
            });
          } else if (action === "Order") {
            navigation.navigate("OrderList", {
              partyId: dealer.id,
              partyName: dealer.name,
            });
          } else if (action === "Payment") {
            navigation.navigate("PaymentCollection", {
              partyId: dealer.id,
              partyName: dealer.name,
            });
          } else if (action === "Party History" || action === "History") {
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
  position,
  onDismiss,
  onAction,
}: {
  visible: boolean;
  dealer: Party | null;
  position: { x: number; y: number };
  onDismiss: () => void;
  onAction: (action: string, dealer: Party) => void;
}) {
  const windowHeight = Dimensions.get("window").height;
  const MENU_HEIGHT = 280;
  // If the 3-dots button is low on the screen, open upwards
  const isNearBottom = position.y > windowHeight - MENU_HEIGHT - 60;
  const topPos = isNearBottom
    ? Math.max(50, position.y - MENU_HEIGHT + 10)
    : Math.max(50, Math.min(position.y + 6, windowHeight - MENU_HEIGHT - 20));

  return (
    <Modal visible={visible} animationType="fade" transparent onRequestClose={onDismiss}>
      <TouchableOpacity
        style={styles.menuOverlay}
        activeOpacity={1}
        onPress={onDismiss}
      >
        <View
          style={[
            styles.menuCard,
            {
              top: topPos,
              right: 16,
            },
          ]}
        >
          <ScrollView
            bounces={false}
            showsVerticalScrollIndicator={false}
            style={{ maxHeight: MENU_HEIGHT }}
          >
            {ACTIONS.map((a, idx) => (
              <TouchableOpacity
                key={a.label}
                style={[
                  styles.menuRow,
                  idx < ACTIONS.length - 1 && styles.menuRowBorder,
                ]}
                activeOpacity={0.7}
                onPress={() => {
                  onDismiss();
                  if (dealer) onAction(a.label, dealer);
                }}
              >
                <MaterialCommunityIcons name={a.icon} size={16} color="#7367F0" />
                <Text style={styles.menuText} numberOfLines={1}>{a.label}</Text>
              </TouchableOpacity>
            ))}
          </ScrollView>
        </View>
      </TouchableOpacity>
    </Modal>
  );
}

const styles = StyleSheet.create({
  safe: {
    flex: 1,
    backgroundColor: "#F4F4F8",
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
  topSearchWrapper: {
    paddingHorizontal: 14,
    paddingTop: 12,
    backgroundColor: "#F4F4F8",
  },
  scrollContent: {
    paddingHorizontal: 14,
    paddingTop: 4,
    paddingBottom: 130,
  },
  searchWrap: {
    backgroundColor: "#FFFFFF",
    borderRadius: 16,
    borderWidth: 1,
    borderColor: "#7367F0",
    paddingHorizontal: 14,
    height: 50,
    flexDirection: "row",
    alignItems: "center",
    justifyContent: "space-between",
    marginBottom: 4,
  },
  searchInput: {
    flex: 1,
    fontSize: 15,
    fontWeight: "700",
    color: "#7367F0",
    paddingVertical: 8,
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
    backgroundColor: "#F5F3FF",
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
    bottom: 24,
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
    backgroundColor: "rgba(0,0,0,0.15)",
  },
  menuCard: {
    position: "absolute",
    width: 170,
    backgroundColor: "#FFFFFF",
    borderRadius: 12,
    paddingVertical: 4,
    borderWidth: 1,
    borderColor: "#E5E7EB",
    shadowColor: "#000",
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.14,
    shadowRadius: 10,
    elevation: 8,
  },
  menuRow: {
    flexDirection: "row",
    alignItems: "center",
    gap: 10,
    paddingVertical: 8,
    paddingHorizontal: 12,
  },
  menuRowBorder: {
    borderBottomWidth: 0.5,
    borderBottomColor: "#F1F5F9",
  },
  menuText: {
    fontWeight: "700",
    fontSize: 12.5,
    color: "#1F2937",
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
  centerLoading: {
    paddingTop: 50,
    alignItems: "center",
  },
  loadingText: {
    marginTop: 12,
    fontSize: 13,
    color: "#6B7280",
    fontWeight: "600",
  },
  footerLoader: {
    paddingVertical: 18,
    alignItems: "center",
    justifyContent: "center",
    flexDirection: "row",
    gap: 8,
  },
  footerLoaderText: {
    fontSize: 12,
    fontWeight: "700",
    color: "#7367F0",
  },
  emptyText: {
    textAlign: "center",
    marginTop: 40,
    color: "#8E8E8E",
    fontWeight: "bold",
    fontSize: 14,
  },
});
