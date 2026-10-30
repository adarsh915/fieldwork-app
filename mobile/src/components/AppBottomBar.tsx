import { MaterialCommunityIcons } from "@expo/vector-icons";
import { useEffect, useMemo, useRef, useState } from "react";
import {
  Animated,
  Keyboard,
  Platform,
  StyleSheet,
  Text,
  TouchableOpacity,
  View,
} from "react-native";
import { useSafeAreaInsets } from "react-native-safe-area-context";
import { navigate, useCurrentRouteName } from "../navigation/navigationRef";

export type BottomTabKey = "Home" | "Bill" | "MIS" | "Leaves" | "Calendar";

type TabItem = {
  key: BottomTabKey;
  label: string;
  icon: keyof typeof MaterialCommunityIcons.glyphMap;
};

const TABS: TabItem[] = [
  { key: "Home",     label: "Home",     icon: "home-outline" },
  { key: "Bill",     label: "Bill",     icon: "receipt" },
  { key: "MIS",      label: "Reports",  icon: "chart-pie" },
  { key: "Leaves",   label: "Leaves",   icon: "calendar-account-outline" },
  { key: "Calendar", label: "Calendar", icon: "calendar-month-outline" },
];

// Tabs not yet implemented — tap shows a toast instead of navigating
const COMING_SOON: BottomTabKey[] = [];

export default function AppBottomBar({
  activeKey,
  onTabPress,
}: {
  activeKey?: BottomTabKey | null;
  onTabPress?: (key: BottomTabKey) => void;
}) {
  const insets = useSafeAreaInsets();
  const currentRoute = useCurrentRouteName();
  const [toastVisible, setToastVisible] = useState(false);
  const [keyboardVisible, setKeyboardVisible] = useState(false);
  const opacity = useRef(new Animated.Value(0)).current;
  const translateY = useRef(new Animated.Value(20)).current;
  const timer = useRef<ReturnType<typeof setTimeout> | null>(null);

  // Hide bottom bar when keyboard is open to keep inputs unobstructed
  useEffect(() => {
    const showEvent = Platform.OS === "ios" ? "keyboardWillShow" : "keyboardDidShow";
    const hideEvent = Platform.OS === "ios" ? "keyboardWillHide" : "keyboardDidHide";

    const showSub = Keyboard.addListener(showEvent, () => setKeyboardVisible(true));
    const hideSub = Keyboard.addListener(hideEvent, () => setKeyboardVisible(false));

    return () => {
      showSub.remove();
      hideSub.remove();
    };
  }, []);

  // Compute active tab from route name if not explicitly passed
  const resolvedActiveKey = useMemo<BottomTabKey | null>(() => {
    if (activeKey !== undefined) return activeKey;
    if (currentRoute === "Dashboard") return "Home";
    if (currentRoute === "Report") return "MIS";
    if (currentRoute === "AttendanceCalendar" || currentRoute === "Calendar") return "Calendar";
    if (
      currentRoute === "ExpenseList" ||
      currentRoute === "AddExpense" ||
      currentRoute === "ExpenseMonthlySummary" ||
      currentRoute === "ExpenseStatement"
    ) {
      return "Bill";
    }
    if (currentRoute === "MyLeaves" || currentRoute === "LeaveApplication") return "Leaves";
    return null;
  }, [activeKey, currentRoute]);

  function showToast() {
    if (timer.current) clearTimeout(timer.current);
    setToastVisible(true);
    Animated.parallel([
      Animated.timing(opacity,    { toValue: 1, duration: 250, useNativeDriver: true }),
      Animated.timing(translateY, { toValue: 0, duration: 250, useNativeDriver: true }),
    ]).start();
    timer.current = setTimeout(() => {
      Animated.parallel([
        Animated.timing(opacity,    { toValue: 0, duration: 300, useNativeDriver: true }),
        Animated.timing(translateY, { toValue: 20, duration: 300, useNativeDriver: true }),
      ]).start(() => setToastVisible(false));
    }, 2500);
  }

  function handlePress(key: BottomTabKey) {
    if (COMING_SOON.includes(key)) {
      showToast();
      return;
    }

    if (onTabPress) {
      onTabPress(key);
    }

    // Do nothing if already on the active tab
    if (resolvedActiveKey && key === resolvedActiveKey) return;

    // Default global navigation across all screens
    if (key === "Calendar") {
      navigate("AttendanceCalendar");
    } else if (key === "Home") {
      navigate("Dashboard");
    } else if (key === "MIS") {
      navigate("Report");
    } else if (key === "Bill") {
      navigate("ExpenseList");
    } else if (key === "Leaves") {
      navigate("MyLeaves");
    }
  }

  if (keyboardVisible) {
    return null;
  }

  return (
    <View style={styles.container}>
      {toastVisible && (
        <Animated.View style={[styles.toast, { opacity, transform: [{ translateY }] }]}>
          <MaterialCommunityIcons name="clock-outline" size={14} color="#fff" style={{ marginRight: 6 }} />
          <Text style={styles.toastText}>This feature is under development. Stay tuned!</Text>
        </Animated.View>
      )}

      <View style={[styles.bar, { paddingBottom: Math.max(insets.bottom, 10) }]}>
        {TABS.map((tab) => {
          const active = resolvedActiveKey ? tab.key === resolvedActiveKey : false;
          return (
            <TouchableOpacity
              key={tab.key}
              style={styles.tab}
              activeOpacity={0.8}
              onPress={() => handlePress(tab.key)}
            >
              <MaterialCommunityIcons
                name={tab.icon}
                size={24}
                color={active ? "#7367F0" : "#9ca3af"}
              />
              <Text style={[styles.label, active && styles.labelActive]}>
                {tab.label}
              </Text>
            </TouchableOpacity>
          );
        })}
      </View>
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    backgroundColor: "#fff",
  },
  toast: {
    position: "absolute",
    bottom: 65,
    alignSelf: "center",
    flexDirection: "row",
    alignItems: "center",
    backgroundColor: "#7367F0",
    paddingHorizontal: 16,
    paddingVertical: 10,
    borderRadius: 24,
    shadowColor: "#7367F0",
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.35,
    shadowRadius: 8,
    elevation: 8,
    maxWidth: "92%",
    zIndex: 999,
  },
  toastText: {
    color: "#fff",
    fontSize: 12,
    fontWeight: "600",
  },
  bar: {
    flexDirection: "row",
    backgroundColor: "#fff",
    borderTopWidth: 1,
    borderTopColor: "#e5e7eb",
    paddingVertical: 8,
    paddingBottom: 10,
  },
  tab: {
    flex: 1,
    alignItems: "center",
    justifyContent: "center",
  },
  label: {
    fontSize: 10,
    fontWeight: "600",
    color: "#9ca3af",
    marginTop: 2,
  },
  labelActive: {
    color: "#7367F0",
    fontWeight: "800",
  },
});
