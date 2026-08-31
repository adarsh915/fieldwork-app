import { MaterialCommunityIcons } from "@expo/vector-icons";
import { useRef, useState } from "react";
import {
  Animated,
  StyleSheet,
  Text,
  TouchableOpacity,
  View,
} from "react-native";
import { useSafeAreaInsets } from "react-native-safe-area-context";

export type BottomTabKey = "Home" | "Track" | "MIS" | "Task" | "Calendar";

type TabItem = {
  key: BottomTabKey;
  label: string;
  icon: keyof typeof MaterialCommunityIcons.glyphMap;
};

const TABS: TabItem[] = [
  { key: "Home",     label: "Home",     icon: "home-outline" },
  { key: "Track",    label: "Track",    icon: "crosshairs-gps" },
  { key: "MIS",      label: "MIS",      icon: "chart-pie" },
  { key: "Task",     label: "Task",     icon: "checkbox-marked-outline" },
  { key: "Calendar", label: "Calendar", icon: "calendar-month-outline" },
];

// Tabs not yet implemented — tap shows a toast instead of navigating
const COMING_SOON: BottomTabKey[] = ["Track", "MIS", "Task", "Calendar"];

export default function AppBottomBar({
  activeKey,
  onTabPress,
}: {
  activeKey: BottomTabKey;
  onTabPress?: (key: BottomTabKey) => void;
}) {
  const insets = useSafeAreaInsets();
  const [toastVisible, setToastVisible] = useState(false);
  const opacity   = useRef(new Animated.Value(0)).current;
  const translateY = useRef(new Animated.Value(20)).current;
  const timer = useRef<ReturnType<typeof setTimeout> | null>(null);

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
    if (COMING_SOON.includes(key)) { showToast(); return; }
    onTabPress?.(key);
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
          const active = tab.key === activeKey;
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
    position: "absolute",
    bottom: 0,
    left: 0,
    right: 0,
  },
  toast: {
    flexDirection: "row",
    alignItems: "center",
    alignSelf: "center",
    backgroundColor: "#7367F0",
    paddingHorizontal: 16,
    paddingVertical: 10,
    borderRadius: 24,
    marginBottom: 8,
    shadowColor: "#7367F0",
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.35,
    shadowRadius: 8,
    elevation: 8,
    maxWidth: "92%",
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
