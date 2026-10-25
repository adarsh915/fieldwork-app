/**
 * Root Navigator
 *
 * Auth-aware navigation:
 *   isLoading = true  → SplashScreen (checking saved token)
 *   user = null       → Auth stack (Login only)
 *   user present      → App stack  (Dashboard, DayStart, etc.)
 *
 * No manual navigation.navigate('Login') or navigate('Dashboard') needed —
 * changing auth state in AuthContext automatically switches the navigator.
 */

/**
 * Backward-compat type exported so existing screens that import
 * RootStackParamList continue to compile without changes.
 */
import { View } from "react-native";
import { createNativeStackNavigator } from "@react-navigation/native-stack";

import { useAuth } from "../auth/AuthContext";
import AppBottomBar from "../components/AppBottomBar";

// Screens
import CalendarScreen from "../screens/CalendarScreen";
import AddPartyScreen from "../screens/AddPartyScreen";
import DashboardScreen from "../screens/DashboardScreen";
import DayEndScreen from "../screens/DayEndScreen";
import DayStartScreen from "../screens/DayStartScreen";
import DealerListScreen from "../screens/DealerListScreen";
import LoginScreen from "../screens/LoginScreen";
import OrderScreen from "../screens/OrderScreen";
import OrderListScreen from "../screens/OrderListScreen";
import OrderDetailsScreen from "../screens/OrderDetailsScreen";
import PaymentCollectionScreen from "../screens/PaymentCollectionScreen";
import PaymentRegisterScreen from "../screens/PaymentRegisterScreen";
import CustomerProfileScreen from "../screens/CustomerProfileScreen";
import ProductListScreen from "../screens/ProductListScreen";
import RewardScreen from "../screens/RewardScreen";
import SplashScreen from "../screens/SplashScreen";
import TrackScreen from "../screens/TrackScreen";
import VisitScreen from "../screens/VisitScreen";
import ExpenseListScreen from "../screens/ExpenseListScreen";
import ExpenseMonthlySummaryScreen from "../screens/ExpenseMonthlySummaryScreen";
import AddExpenseScreen from "../screens/AddExpenseScreen";
import AttendanceCalendarScreen from "../screens/AttendanceCalendarScreen";
import MyLeavesScreen from "../screens/MyLeavesScreen";
import LeaveApplicationScreen from "../screens/LeaveApplicationScreen";
import ReportScreen from "../screens/ReportScreen";
import NotificationsScreen from "../screens/NotificationsScreen";
import UserProfileScreen from "../screens/UserProfileScreen";
import ApprovalsHubScreen from "../screens/admin/ApprovalsHubScreen";

export type RootStackParamList = {
  ApprovalsHub: undefined;
  // App screens
  Dashboard: undefined;
  Report: undefined;
  DayStart: undefined;
  DayEnd: undefined;
  DealerList: undefined;
  ProductList: undefined;
  Notifications: undefined;
  Visit: {
    partyId: number;
    partyName: string;
    partyMobile: string | null;
    partyAddress: string | null;
  };
  Order: {
    visitId?: number | null;
    partyId: number;
    partyName?: string;
    partyMobile?: string | null;
    partyAddress?: string | null;
    partyCity?: string | null;
    isDirectPhoneOrder?: boolean;
  };
  OrderList: { partyId?: number; partyName?: string } | undefined;
  OrderDetails: { order: any };
  PaymentCollection: {
    partyId: number;
    partyName: string;
    visitId?: number;
  };
  PaymentRegister: undefined;
  CustomerProfile: { partyId: number };
  Track: undefined;
  Reward: undefined;
  Calendar: undefined;
  ExpenseList: undefined;
  ExpenseMonthlySummary: undefined;
  ExpenseStatement: { month: string };
  AddExpense: { expense?: import("../api/expense").ExpenseBill } | undefined;
  AddParty: undefined;
  AttendanceCalendar: undefined;
  MyLeaves: undefined;
  LeaveApplication: undefined;
  UserProfile: undefined;
  // Admin screens (future)
  AdminDashboard: undefined;
  EmployeeList: undefined;
  CreateEmployee: undefined;
};

// ─── Param lists ───────────────────────────────────────────────────────────────

export type AuthStackParamList = {
  Login: undefined;
};

export type AppStackParamList = {
  Dashboard: undefined;
  Report: undefined;
  DayStart: undefined;
  DayEnd: undefined;
  DealerList: undefined;
  ProductList: undefined;
  Notifications: undefined;
  Visit: {
    partyId: number;
    partyName: string;
    partyMobile: string | null;
    partyAddress: string | null;
  };
  Order: {
    visitId?: number | null;
    partyId: number;
    partyName?: string;
    partyMobile?: string | null;
    partyAddress?: string | null;
    partyCity?: string | null;
    isDirectPhoneOrder?: boolean;
  };
  OrderList: { partyId?: number; partyName?: string } | undefined;
  OrderDetails: { order: any };
  PaymentCollection: {
    partyId: number;
    partyName: string;
    visitId?: number;
  };
  PaymentRegister: undefined;
  CustomerProfile: { partyId: number };
  Track: undefined;
  Reward: undefined;
  Calendar: undefined;
  ExpenseList: undefined;
  ExpenseMonthlySummary: undefined;
  ExpenseStatement: { month: string };
  AddExpense: { expense?: import("../api/expense").ExpenseBill } | undefined;
  AddParty: undefined;
  AttendanceCalendar: undefined;
  MyLeaves: undefined;
  LeaveApplication: undefined;
  UserProfile: undefined;
  ApprovalsHub: undefined;
};

// ─── Stacks ────────────────────────────────────────────────────────────────────

const AuthStack = createNativeStackNavigator<AuthStackParamList>();
const AppStack = createNativeStackNavigator<AppStackParamList>();

function AuthNavigator() {
  return (
    <AuthStack.Navigator screenOptions={{ headerShown: false }}>
      <AuthStack.Screen name="Login" component={LoginScreen} />
    </AuthStack.Navigator>
  );
}

function AppNavigator() {
  return (
    <View style={{ flex: 1, backgroundColor: "#fff" }}>
      <View style={{ flex: 1 }}>
        <AppStack.Navigator
          initialRouteName="Dashboard"
          screenOptions={{ headerShown: false }}
        >
          <AppStack.Screen name="Dashboard" component={DashboardScreen} />
          <AppStack.Screen name="Report" component={ReportScreen} />
          <AppStack.Screen name="Notifications" component={NotificationsScreen} />
          <AppStack.Screen name="DayStart" component={DayStartScreen} />
          <AppStack.Screen name="DayEnd" component={DayEndScreen} />
          <AppStack.Screen name="DealerList" component={DealerListScreen} />
          <AppStack.Screen name="ProductList" component={ProductListScreen} />
          <AppStack.Screen name="Visit" component={VisitScreen} />
          <AppStack.Screen name="Order" component={OrderScreen} />
          <AppStack.Screen name="OrderList" component={OrderListScreen} />
          <AppStack.Screen name="OrderDetails" component={OrderDetailsScreen} />
          <AppStack.Screen name="PaymentCollection" component={PaymentCollectionScreen} />
          <AppStack.Screen name="PaymentRegister" component={PaymentRegisterScreen} />
          <AppStack.Screen name="CustomerProfile" component={CustomerProfileScreen} />
          <AppStack.Screen name="Track" component={TrackScreen} />
          <AppStack.Screen name="Reward" component={RewardScreen} />
          <AppStack.Screen name="Calendar" component={AttendanceCalendarScreen} />
          <AppStack.Screen name="ExpenseList" component={ExpenseListScreen} />
          <AppStack.Screen name="ExpenseMonthlySummary" component={ExpenseMonthlySummaryScreen} />
          <AppStack.Screen name="ExpenseStatement" component={require('../screens/ExpenseStatementScreen').default} />
          <AppStack.Screen name="AddExpense" component={AddExpenseScreen} />
          <AppStack.Screen name="AddParty" component={AddPartyScreen} />
          <AppStack.Screen name="AttendanceCalendar" component={AttendanceCalendarScreen} />
          <AppStack.Screen name="MyLeaves" component={MyLeavesScreen} />
          <AppStack.Screen name="LeaveApplication" component={LeaveApplicationScreen} />
          <AppStack.Screen name="UserProfile" component={UserProfileScreen} />
          <AppStack.Screen name="ApprovalsHub" component={ApprovalsHubScreen} />
        </AppStack.Navigator>
      </View>
      <AppBottomBar />
    </View>
  );
}

// ─── Root ──────────────────────────────────────────────────────────────────────

const RootStack = createNativeStackNavigator();

export default function RootNavigator() {
  const { user, isLoading } = useAuth();

  return (
    <RootStack.Navigator
      screenOptions={{ headerShown: false, animation: "fade" }}
    >
      {isLoading ? (
        // Show splash while restoring session
        <RootStack.Screen name="Splash" component={SplashScreen} />
      ) : user ? (
        // Logged in → app screens
        <RootStack.Screen name="App" component={AppNavigator} />
      ) : (
        // Not logged in → auth screens
        <RootStack.Screen name="Auth" component={AuthNavigator} />
      )}
    </RootStack.Navigator>
  );
}
