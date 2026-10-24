import { MaterialCommunityIcons, MaterialIcons } from "@expo/vector-icons";
import { useNavigation, useRoute } from "@react-navigation/native";
import type { RouteProp } from "@react-navigation/native";
import type { NativeStackNavigationProp } from "@react-navigation/native-stack";
import { useEffect, useState } from "react";
import {
  ActivityIndicator,
  Alert,
  ScrollView,
  StyleSheet,
  Text,
  TouchableOpacity,
  View,
} from "react-native";
import { SafeAreaView } from "react-native-safe-area-context";

import { getPartyDetails, type PartyProfile } from "../api/party";
import ScreenHeader from "../components/ScreenHeader";
import type { AppStackParamList } from "../navigation/RootNavigator";

type Nav = NativeStackNavigationProp<AppStackParamList, "CustomerProfile">;
type RouteType = RouteProp<AppStackParamList, "CustomerProfile">;

export default function CustomerProfileScreen() {
  const navigation = useNavigation<Nav>();
  const route = useRoute<RouteType>();
  const { partyId } = route.params;

  const [loading, setLoading] = useState(true);
  const [profile, setProfile] = useState<PartyProfile | null>(null);

  useEffect(() => {
    getPartyDetails(partyId)
      .then((res) => {
        if (res.success) {
          setProfile(res.data);
        } else {
          Alert.alert("Error", "Could not load party details");
        }
      })
      .catch((err) => {
        Alert.alert("Error", err.message || "Failed to fetch profile");
      })
      .finally(() => setLoading(false));
  }, [partyId]);

  if (loading) {
    return (
      <View style={styles.safe}>
        <ScreenHeader
          title="Customer Profile"
          onBack={() => navigation.goBack()}
        />
        <ActivityIndicator size="large" color="#7367F0" style={{ marginTop: 40 }} />
      </View>
    );
  }

  if (!profile) {
    return (
      <View style={styles.safe}>
        <ScreenHeader
          title="Customer Profile"
          onBack={() => navigation.goBack()}
        />
        <Text style={{ textAlign: 'center', marginTop: 40, color: '#666' }}>Profile not found.</Text>
      </View>
    );
  }

  const renderField = (label: string, value: string | number | undefined, iconName: any) => (
    <View style={styles.fieldRow}>
      <View style={styles.fieldIcon}>
        <MaterialCommunityIcons name={iconName} size={20} color="#7367F0" />
      </View>
      <View style={styles.fieldContent}>
        <Text style={styles.fieldLabel}>{label}</Text>
        <Text style={styles.fieldValue}>{value || "N/A"}</Text>
      </View>
    </View>
  );

  return (
    <View style={styles.safe}>
      <ScreenHeader
        title="Customer Profile"
        onBack={() => navigation.goBack()}
      />

      <ScrollView contentContainerStyle={styles.scrollContent}>
        {/* Main Info Card */}
        <View style={styles.card}>
          <View style={styles.avatarContainer}>
            <MaterialCommunityIcons name="store" size={40} color="#fff" />
          </View>
          <Text style={styles.partyName}>{profile.name}</Text>
          <Text style={styles.partyCode}>Code: {profile.party_code}</Text>
          
          <View style={styles.statusBadge}>
            <Text style={styles.statusText}>{profile.is_active ? 'ACTIVE' : 'INACTIVE'}</Text>
          </View>
        </View>

        {/* Details Card */}
        <View style={styles.detailsCard}>
          <Text style={styles.sectionTitle}>Contact Information</Text>
          {renderField("Owner Name", profile.owner_name, "account")}
          {renderField("Mobile", profile.mobile, "phone")}
          {renderField("Email", profile.email, "email")}

          <View style={styles.divider} />
          
          <Text style={styles.sectionTitle}>Address Details</Text>
          {renderField("Address", profile.address, "map-marker")}
          {renderField("City", profile.city, "city")}
          {renderField("State", profile.state, "map")}
          {renderField("Pincode", profile.pincode, "mailbox")}

          <View style={styles.divider} />
          
          <Text style={styles.sectionTitle}>Business Details</Text>
          {renderField("Party Type", profile.party_type, "briefcase")}
          {renderField("Category", profile.category, "tag")}
          {renderField("Credit Limit", `₹${profile.credit_limit || 0}`, "credit-card")}
          {renderField("Credit Days", profile.credit_days, "calendar-clock")}
          {renderField("Opening Balance", `₹${profile.opening_balance || 0}`, "bank")}
        </View>
      </ScrollView>
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
  scrollContent: { padding: 16, paddingBottom: 40 },
  card: {
    backgroundColor: "#fff",
    borderRadius: 16,
    padding: 24,
    alignItems: "center",
    marginBottom: 16,
    elevation: 2,
    shadowColor: "#000",
    shadowOpacity: 0.05,
    shadowRadius: 10,
  },
  avatarContainer: {
    width: 80,
    height: 80,
    borderRadius: 40,
    backgroundColor: "#7367F0",
    alignItems: "center",
    justifyContent: "center",
    marginBottom: 16,
    elevation: 4,
  },
  partyName: {
    fontSize: 22,
    fontWeight: "900",
    color: "#111827",
    textAlign: "center",
    marginBottom: 4,
  },
  partyCode: {
    fontSize: 14,
    color: "#6B7280",
    fontWeight: "600",
    marginBottom: 12,
  },
  statusBadge: {
    backgroundColor: "#10B981",
    paddingHorizontal: 12,
    paddingVertical: 4,
    borderRadius: 20,
  },
  statusText: {
    color: "#fff",
    fontSize: 12,
    fontWeight: "bold",
  },
  detailsCard: {
    backgroundColor: "#fff",
    borderRadius: 16,
    padding: 20,
    elevation: 2,
    shadowColor: "#000",
    shadowOpacity: 0.05,
    shadowRadius: 10,
  },
  sectionTitle: {
    fontSize: 16,
    fontWeight: "800",
    color: "#374151",
    marginBottom: 16,
  },
  divider: {
    height: 1,
    backgroundColor: "#F3F4F6",
    marginVertical: 20,
  },
  fieldRow: {
    flexDirection: "row",
    alignItems: "center",
    marginBottom: 16,
  },
  fieldIcon: {
    width: 40,
    height: 40,
    borderRadius: 10,
    backgroundColor: "#F3F0FF",
    alignItems: "center",
    justifyContent: "center",
    marginRight: 16,
  },
  fieldContent: {
    flex: 1,
  },
  fieldLabel: {
    fontSize: 12,
    color: "#6B7280",
    fontWeight: "600",
    textTransform: "uppercase",
    marginBottom: 2,
  },
  fieldValue: {
    fontSize: 15,
    color: "#111827",
    fontWeight: "600",
  },
});
